<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        /** @var User $targetUser */
        $targetUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($targetUser->id),
            ],
            'role' => ['required', Rule::enum(UserRole::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var User $targetUser */
            $targetUser = $this->route('user');
            $newRole = UserRole::from($this->input('role'));

            if ($targetUser->isDev() && $newRole === UserRole::User) {
                $devCount = User::query()->where('role', UserRole::Dev)->count();

                if ($devCount <= 1) {
                    $validator->errors()->add(
                        'role',
                        'Não é possível remover a role dev do último usuário dev do sistema.'
                    );
                }
            }
        });
    }
}
