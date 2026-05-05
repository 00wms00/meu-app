<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index()
    {
        $categorias = Category::where('user_id', Auth::id())
            ->withCount('products')
            ->ordenado()
            ->get();
        
        return view('categories.index', compact('categorias'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:100',
            'emoji' => 'nullable|string|max:10',
            'cor' => 'nullable|string|max:20',
            'descricao' => 'nullable|string|max:500',
            'ordem' => 'nullable|integer',
        ]);

        // Verificar se já existe com mesmo nome
        $exists = Category::where('user_id', Auth::id())
            ->where('nome', $validated['nome'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['nome' => 'Já existe uma categoria com este nome.']);
        }

        Category::create([
            'user_id' => Auth::id(),
            'nome' => $validated['nome'],
            'emoji' => $validated['emoji'] ?? null,
            'cor' => $validated['cor'] ?? '#3b82f6',
            'descricao' => $validated['descricao'] ?? null,
            'ordem' => $validated['ordem'] ?? 0,
        ]);

        return redirect()->route('categories.index')
            ->with('success', "Categoria \"{$validated['nome']}\" criada com sucesso!");
    }

    public function update(Request $request, Category $category)
    {
        if ($category->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:100',
            'emoji' => 'nullable|string|max:10',
            'cor' => 'nullable|string|max:20',
            'descricao' => 'nullable|string|max:500',
            'ordem' => 'nullable|integer',
        ]);

        // Verificar duplicata
        $exists = Category::where('user_id', Auth::id())
            ->where('nome', $validated['nome'])
            ->where('id', '!=', $category->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['nome' => 'Já existe uma categoria com este nome.']);
        }

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('success', "Categoria \"{$validated['nome']}\" atualizada!");
    }

    public function destroy(Category $category)
    {
        if ($category->user_id !== Auth::id()) {
            abort(403);
        }

        $count = $category->products()->count();
        $nome = $category->nome;

        // Desvincular produtos
        $category->products()->update(['category_id' => null]);
        
        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', "Categoria \"{$nome}\" excluída! {$count} produto(s) desvinculado(s).");
    }

    // API para buscar categorias (usado em selects)
    public function apiIndex()
    {
        $categorias = Category::where('user_id', Auth::id())
            ->ordenado()
            ->get()
            ->map(function($cat) {
                return [
                    'id' => $cat->id,
                    'nome' => $cat->nome,
                    'emoji' => $cat->emoji,
                    'cor' => $cat->cor,
                    'produtos_count' => $cat->products()->count(),
                ];
            });
        
        return response()->json($categorias);
    }
}
