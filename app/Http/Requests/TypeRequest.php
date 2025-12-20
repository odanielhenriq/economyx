<?php

namespace App\Http\Requests;

use App\Models\Type;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->route('type');
        $typeId = $type instanceof Type ? $type->id : (int) $this->route('id');

        $slugRule = Rule::unique('types', 'slug');
        if ($typeId) {
            $slugRule = $slugRule->ignore($typeId);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', $slugRule],
            'color' => ['nullable', 'string', 'max:20'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug') ?: $this->input('name');

        if ($slug !== null) {
            $this->merge(['slug' => Str::slug($slug)]);
        }
    }
}
