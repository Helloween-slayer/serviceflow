<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
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
     * Збереження нового тега
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
        ]);

        Tag::create($validated);

        return redirect()->back()->with('success', 'Тег успішно створено');
    }

    /**
     * Оновлення тега
     */
    public function update(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name,' . $tag->id,
        ]);

        $tag->update($validated);

        return redirect()->back()->with('success', 'Тег успішно оновлено');
    }

    /**
     * Видалення тега
     */
    public function destroy(Tag $tag)
    {
        if ($tag->orders()->count() > 0) {
            return redirect()->back()->with('error', 'Неможливо видалити тег, який використовується в заявках');
        }

        $tagName = $tag->name;
        $tag->delete();

        return redirect()->back()->with('success', "Тег '{$tagName}' успішно видалено");
    }
}
