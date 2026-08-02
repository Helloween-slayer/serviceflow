<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Список усіх користувачів (тільки для адміна)
     */
    public function index(Request $request)
    {
        // Перевіряємо: чи може адмін переглядати список?
        Gate::authorize('viewAny', User::class);

        $query = User::with('role');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role_id', $request->role);
        }

        $users = $query->paginate(10)->withQueryString();
        $roles = Role::all();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    /**
     * Форма редагування користувача
     */
    public function edit(User $user)
    {
        // Перевіряємо: чи може користувач редагувати цей профіль?
        Gate::authorize('update', $user);

        $user->load('role');
        $roles = Role::all();

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Оновлення користувача (адмін може змінювати роль та дані)
     */
    public function update(Request $request, User $user)
    {
        // Перевіряємо: чи може користувач редагувати цей профіль?
        Gate::authorize('update', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('admin.users.index')
            ->with('success', "Користувача {$user->name} успішно оновлено");
    }

    /**
     * Видалення користувача (тільки для адміна)
     */
    public function destroy(User $user)
    {
        // Перевіряємо: чи може адмін видаляти цього користувача?
        Gate::authorize('delete', $user);

        // 🔒 Додаткова бізнес-логіка
        // Не можна видаляти самого себе
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Ви не можете видалити самого себе');
        }

        // Не можна видаляти останнього адміна
        $adminCount = User::where('role_id', 1)->count();
        if ($user->role_id === 1 && $adminCount <= 1) {
            return back()->with('error', 'Не можна видалити останнього адміністратора');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Користувача {$userName} успішно видалено");
    }
}
