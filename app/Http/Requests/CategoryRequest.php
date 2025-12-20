<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = $category instanceof Category ? $category->id : (int) $this->route('id');

        $slugRule = Rule::unique('categories', 'slug');
        if ($categoryId) {
            $slugRule = $slugRule->ignore($categoryId);
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
