<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determina se o usuário pode visualizar a categoria.
     * Usado por $this->authorize('view', $category) e @can('view', $category) nas views.
     */
    public function view(User $user, Category $category): bool
    {
        return $category->user_id === $user->id;
    }

    /**
     * Determina se o usuário pode atualizar a categoria.
     * Substitui: if ($category->user_id !== Auth::id()) abort(403)
     */
    public function update(User $user, Category $category): bool
    {
        return $category->user_id === $user->id;
    }

    /**
     * Determina se o usuário pode excluir a categoria.
     * Substitui: if ($category->user_id !== Auth::id()) abort(403)
     */
    public function delete(User $user, Category $category): bool
    {
        return $category->user_id === $user->id;
    }
}
