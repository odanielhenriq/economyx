<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\TypeRequest;
use App\Models\Type;
use App\Repositories\TypeRepositoryInterface;
use App\Support\ReferenceSlugs;

class TypeWebController extends Controller
{
    public function __construct(
        private TypeRepositoryInterface $types
    ) {}

    public function index()
    {
        return view('settings.types.index');
    }

    public function create()
    {
        abort(404);
    }

    public function store(TypeRequest $request)
    {
        abort(404);
    }

    public function edit(Type $type)
    {
        if (ReferenceSlugs::isSystemTypeSlug($type->slug)) {
            return redirect()
                ->route('types.index')
                ->withErrors(['type' => 'Este tipo é usado pelo sistema e não pode ser alterado.']);
        }

        return view('settings.types.edit', compact('type'));
    }

    public function update(TypeRequest $request, Type $type)
    {
        if (ReferenceSlugs::isSystemTypeSlug($type->slug)) {
            return redirect()
                ->route('types.index')
                ->withErrors(['type' => 'Este tipo é usado pelo sistema e não pode ser alterado.']);
        }

        $data = $request->validated();

        $this->types->update($type->id, $data);

        return redirect()
            ->route('types.index')
            ->with('success', 'Tipo atualizado com sucesso!');
    }

    public function destroy(Type $type)
    {
        if (ReferenceSlugs::isSystemTypeSlug($type->slug)) {
            return redirect()
                ->route('types.index')
                ->withErrors(['type' => 'Este tipo é usado pelo sistema e não pode ser excluído.']);
        }

        $this->types->delete($type->id);

        return redirect()
            ->route('types.index')
            ->with('success', 'Tipo removido com sucesso!');
    }
}
