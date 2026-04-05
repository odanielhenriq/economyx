<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryBudgetController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get();

        $budgets = CategoryBudget::where('user_id', Auth::id())
            ->pluck('amount', 'category_id'); // category_id => amount

        return view('settings.budgets.index', compact('categories', 'budgets'));
    }

    /**
     * Cria ou atualiza o orçamento de uma categoria (upsert).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount'       => 'required|numeric|min:0.01',
        ]);

        CategoryBudget::updateOrCreate(
            ['user_id' => Auth::id(), 'category_id' => $data['category_id']],
            ['amount' => $data['amount']]
        );

        return redirect()
            ->route('budgets.index')
            ->with('success', 'Orçamento salvo com sucesso!');
    }

    public function destroy(CategoryBudget $budget)
    {
        abort_if($budget->user_id !== Auth::id(), 403);

        $budget->delete();

        return redirect()
            ->route('budgets.index')
            ->with('success', 'Orçamento removido.');
    }
}
