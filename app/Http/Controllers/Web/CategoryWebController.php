<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Repositories\CategoryRepositoryInterface;

class CategoryWebController extends Controller
{
    public function __construct(
        private CategoryRepositoryInterface $categories
    ) {}

    public function index()
    {
        return view('settings.categories.index');
    }

    public function create()
    {
        return view('settings.categories.create');
    }

    public function store(CategoryRequest $request)
    {
        $data = $request->validated();

        $this->categories->create($data);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoria criada com sucesso!');
    }

    public function edit(Category $category)
    {
        return view('settings.categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $data = $request->validated();

        $this->categories->update($category->id, $data);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(Category $category)
    {
        $this->categories->delete($category->id);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoria removida com sucesso!');
    }

}
