<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Вивести список усіх користувачів (тільки для адміна)
     * З можливістю пошуку та фільтрації за роллю
     */
    public function index(Request $request)
    {
        $query = User::with('role');

        // Поиск по имени или email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Фильтр по роли
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
        // Підвантажуємо роль користувача
        $user->load('role');

        // Отримуємо всі ролі для випадаючого списку
        $roles = Role::all();

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Оновлення даних користувача (адмін може змінювати роль та дані)
     */
    public function update(Request $request, User $user)
    {
        // Валідація вхідних даних
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Підготовка даних для оновлення
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
        ];

        // Якщо пароль вказаний - хешуємо та додаємо до оновлення
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        // Виконуємо оновлення
        $user->update($updateData);

        // Редирект на список з повідомленням про успіх
        return redirect()->route('admin.users.index')
            ->with('success', "Користувача {$user->name} успішно оновлено");
    }

    /**
     * Видалення користувача (тільки для адміна)
     * З додатковими перевірками безпеки
     */
    public function destroy(User $user)
    {
        // Забороняємо видаляти самого себе
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Ви не можете видалити самого себе');
        }

        // Забороняємо видаляти останнього адміністратора
        // Щоб не залишити систему без адміна
        $adminCount = User::where('role_id', 1)->count();
        if ($user->role_id === 1 && $adminCount <= 1) {
            return back()->with('error', 'Не можна видалити останнього адміністратора');
        }

        // Зберігаємо ім'я для повідомлення
        $userName = $user->name;

        // Видаляємо користувача
        $user->delete();

        // Редирект з повідомленням про успіх
        return redirect()->route('admin.users.index')
            ->with('success', "Користувача {$userName} успішно видалено");
    }
}
