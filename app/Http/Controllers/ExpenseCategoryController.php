<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseCategoryController extends Controller
{
    /** Lista todas as categorias do usuário como JSON (para o modal). */
    public function index()
    {
        $cats = ExpenseCategory::doUsuario(Auth::id())->get();
        return response()->json($cats);
    }

    /** Cria uma nova categoria. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'  => 'required|string|max:80',
            'cor'   => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'emoji' => 'nullable|string|max:10',
        ]);

        $exists = ExpenseCategory::where('user_id', Auth::id())
            ->where('nome', $data['nome'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Categoria já existe.'], 422);
        }

        $maxOrdem = ExpenseCategory::where('user_id', Auth::id())->max('ordem') ?? 0;

        $cat = ExpenseCategory::create([
            'user_id' => Auth::id(),
            'nome'    => $data['nome'],
            'cor'     => $data['cor'],
            'emoji'   => $data['emoji'] ?? null,
            'ordem'   => $maxOrdem + 1,
        ]);

        return response()->json($cat, 201);
    }

    /** Atualiza nome / cor / emoji de uma categoria. */
    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        abort_unless($expenseCategory->user_id === Auth::id(), 403);

        $data = $request->validate([
            'nome'  => 'required|string|max:80',
            'cor'   => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'emoji' => 'nullable|string|max:10',
        ]);

        $expenseCategory->update($data);

        return response()->json($expenseCategory);
    }

    /** Remove uma categoria (não remove as despesas, só limpa o campo). */
    public function destroy(ExpenseCategory $expenseCategory)
    {
        abort_unless($expenseCategory->user_id === Auth::id(), 403);
        $expenseCategory->delete();
        return response()->json(['ok' => true]);
    }

    /** Reordena as categorias. Recebe [{id, ordem}, ...] */
    public function reorder(Request $request)
    {
        $request->validate(['items' => 'required|array']);

        foreach ($request->items as $item) {
            ExpenseCategory::where('id', $item['id'])
                ->where('user_id', Auth::id())
                ->update(['ordem' => $item['ordem']]);
        }

        return response()->json(['ok' => true]);
    }
}
