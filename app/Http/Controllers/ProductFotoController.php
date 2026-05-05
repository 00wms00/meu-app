<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductFotoController extends Controller
{
    public function upload(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $request->validate(['foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048']);

        if ($product->foto) {
            Storage::disk('public')->delete($product->foto);
        }

        $product->update([
            'foto' => $request->file('foto')->store('produtos/' . Auth::id(), 'public'),
        ]);

        return back()->with('success', 'Foto atualizada!');
    }

    public function remover(Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        if ($product->foto) {
            Storage::disk('public')->delete($product->foto);
        }

        $product->update(['foto' => null]);

        return back()->with('success', 'Foto removida!');
    }
}
