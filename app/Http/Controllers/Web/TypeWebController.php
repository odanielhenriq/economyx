<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\TypeRequest;
use App\Models\Type;
use App\Repositories\TypeRepositoryInterface;

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
        return view('settings.types.create');
    }

    public function store(TypeRequest $request)
    {
        $data = $request->validated();

        $this->types->create($data);

        return redirect()
            ->route('types.index')
            ->with('success', 'Tipo criado com sucesso!');
    }

    public function edit(Type $type)
    {
        return view('settings.types.edit', compact('type'));
    }

    public function update(TypeRequest $request, Type $type)
    {
        $data = $request->validated();

        $this->types->update($type->id, $data);

        return redirect()
            ->route('types.index')
            ->with('success', 'Tipo atualizado com sucesso!');
    }

    public function destroy(Type $type)
    {
        $this->types->delete($type->id);

        return redirect()
            ->route('types.index')
            ->with('success', 'Tipo removido com sucesso!');
    }

}
