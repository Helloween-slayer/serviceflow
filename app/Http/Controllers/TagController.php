<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TagController extends Controller
{
    /**
     * Вивести список усіх тегів з пошуком та пагінацією
     */
    public function index(Request $request)
    {
        $query = Tag::withCount('orders');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $tags = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Tags/Index', [
            'tags' => $tags,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Збереження нового тега (тільки для адміна)
     */
    public function store(Request $request)
    {
        // Перевіряємо: чи може адмін створювати теги?
        Gate::authorize('create', Tag::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
        ]);

        Tag::create($validated);

        return redirect()->back()->with('success', 'Тег успішно створено');
    }

    /**
     * Оновлення тега (тільки для адміна)
     */
    public function update(Request $request, Tag $tag)
    {
        // Перевіряємо: чи може адмін редагувати цей тег?
        Gate::authorize('update', $tag);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name,' . $tag->id,
        ]);

        $tag->update($validated);

        return redirect()->back()->with('success', 'Тег успішно оновлено');
    }

    /**
     * Видалення тега (тільки для адміна)
     */
    public function destroy(Tag $tag)
    {
        // Перевіряємо: чи може адмін видаляти цей тег?
        Gate::authorize('delete', $tag);

        // 🔒 Бізнес-логіка: не можна видаляти тег, який використовується
        if ($tag->orders()->count() > 0) {
            return redirect()->back()->with('error', 'Неможливо видалити тег, який використовується в заявках');
        }

        $tagName = $tag->name;
        $tag->delete();

        return redirect()->back()->with('success', "Тег '{$tagName}' успішно видалено");
    }
}
