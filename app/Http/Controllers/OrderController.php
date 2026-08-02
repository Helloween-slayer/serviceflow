<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Tag;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Публічний список — тільки доступні заявки (status = new, worker_id = null).
     * Для всіх користувачів (включаючи неавторизованих)
     */
    public function index()
    {
        $orders = OrderService::getAvailableOrders();
        return Inertia::render('Orders/Index', ['orders' => $orders]);
    }

    /**
     * Заявки поточного клієнта.
     * Тільки для клієнтів
     */
    public function clientOrders(Request $request, OrderService $service)
    {

        Gate::authorize('viewAny', Order::class);

        $orders = $service->getClientOrders(auth()->id(), $request->status);
        return Inertia::render('Client/Orders/Index', [
            'orders' => $orders,
            'activeTab' => $request->status ?? 'active'
        ]);
    }

    /**
     * Заявки поточного воркера.
     * Тільки для воркерів
     */
    public function workerOrders(Request $request, OrderService $service)
    {
        Gate::authorize('viewAny', Order::class);

        $orders = $service->getWorkerOrders(auth()->id(), $request->status);
        return Inertia::render('Worker/Orders/Index', [
            'orders' => $orders,
            'activeTab' => $request->status ?? 'active'
        ]);
    }

    /**
     * Адмін-дашборд: статистика та останні заявки.
     * Тільки для адміна
     */
    public function adminDashboard(OrderService $service)
    {
        Gate::authorize('viewAny', Order::class);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $service->getStats(),
            'recentOrders' => $service->getRecentOrders(),
        ]);
    }

    /**
     * Адмін: список всіх заявок.
     * Тільки для адміна
     */
    public function adminOrders(Request $request, OrderService $service)
    {
        //  Перевіряємо: чи має адмін право переглядати всі заявки?
        Gate::authorize('viewAny', Order::class);

        $status = $request->input('status');

        if ($status && $status !== 'all') {
            $orders = $service->getAllOrders($status);
        } else {
            $orders = $service->getAllOrders();
        }

        return Inertia::render('Admin/Orders/Index', ['orders' => $orders]);
    }

    /**
     * Взяти заявку в роботу (тільки для виконавця).
     */
    public function takeOrder(Order $order)
    {
        //  Перевіряємо: чи може воркер взяти цю заявку?
        Gate::authorize('take', $order);

        // 🔒 Бізнес-логіка (додатковий захист)
        if ($order->status !== 'new' || $order->worker_id !== null) {
            return redirect()->back()->with('error', 'Ця заявка вже не доступна.');
        }

        $order->update([
            'worker_id' => auth()->id(),
            'status' => 'in_progress',
        ]);

        return redirect()->route('worker.orders.index')->with('success', 'Заявку взято в роботу.');
    }

    /**
     * Форма створення заявки.
     * Тільки для клієнтів
     */
    public function create()
    {
        //  Перевіряємо: чи може користувач створювати заявки?
        Gate::authorize('create', Order::class);

        $tags = Tag::all();
        return Inertia::render('Client/Orders/Create', ['tags' => $tags]);
    }

    /**
     * Збереження заявки.
     * Тільки для клієнтів
     */
    public function store(Request $request)
    {
        //  Перевіряємо: чи може користувач створювати заявки?
        Gate::authorize('create', Order::class);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'deadline' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $order = Order::create([
            'client_id' => auth()->id(),
            'title' => $data['title'],
            'description' => $data['description'],
            'price' => $data['price'],
            'deadline' => $data['deadline'],
            'status' => 'new',
        ]);

        if (!empty($data['tags'])) {
            $order->tags()->attach($data['tags']);
        }

        return redirect()->route('client.orders.index')->with('success', 'Заявка створена');
    }

    /**
     * Перегляд однієї заявки (публічний).
     * Доступно для всіх авторизованих
     */
    public function show(Order $order)
    {
        //  Перевіряємо: чи може користувач переглядати цю заявку?
        Gate::authorize('view', $order);

        $order->load('tags', 'client', 'worker');
        return Inertia::render('Orders/Show', ['order' => $order]);
    }

    /**
     * Форма редагування заявки (клієнт).
     */
    public function edit(Order $order)
    {
        //  Перевіряємо: чи може користувач редагувати цю заявку?
        Gate::authorize('update', $order);

        // 🔒 Бізнес-логіка: тільки нові заявки можна редагувати
        if ($order->status !== 'new') {
            return redirect()->route('client.orders.index')
                ->with('error', 'Можна редагувати тільки нові заявки');
        }

        $tags = Tag::all();
        return Inertia::render('Client/Orders/Edit', [
            'order' => $order->load('tags'),
            'tags' => $tags,
        ]);
    }

    /**
     * Оновлення заявки (клієнт).
     */
    public function update(Request $request, Order $order)
    {
        //  Перевіряємо: чи може користувач оновлювати цю заявку?
        Gate::authorize('update', $order);

        // 🔒 Бізнес-логіка: тільки нові заявки можна оновлювати
        if ($order->status !== 'new') {
            return redirect()->route('client.orders.index')
                ->with('error', 'Можна редагувати тільки нові заявки');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'deadline' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $order->update($data);

        if ($request->has('tags')) {
            $order->tags()->sync($data['tags']);
        } else {
            $order->tags()->detach();
        }

        return redirect()->route('client.orders.index')->with('success', 'Заявка оновлена');
    }

    /**
     * Видалення заявки.
     * Тільки для клієнта-власника (або адміна)
     */
    public function destroy(Order $order)
    {
        //  Перевіряємо: чи може користувач видаляти цю заявку?
        Gate::authorize('delete', $order);

        // 🔒 Бізнес-логіка: тільки нові заявки можна видаляти
        if ($order->status !== 'new') {
            return redirect()->route('orders.index')
                ->with('error', 'Можна видаляти тільки нові заявки');
        }

        try {
            $order->delete();
            return redirect()->route('orders.index')->with('success', 'Заявку успішно видалено');
        } catch (\Exception $e) {
            \Log::error('Помилка видалення заявки ID: ' . $order->id . ' - ' . $e->getMessage());

            return back()->withErrors([
                'error' => 'Не вдалося видалити заявку. Спробуйте пізніше.'
            ]);
        }
    }

    /**
     * Завершення заявки (тільки для воркера)
     */
    public function complete(Order $order)
    {
        //  Перевіряємо: чи може воркер завершити цю заявку?
        Gate::authorize('complete', $order);

        // 🔒 Бізнес-логіка: тільки заявки "в роботі" можна завершувати
        if ($order->status !== 'in_progress' || $order->worker_id !== auth()->id()) {
            return redirect()->back()->with('error', 'Ви не можете завершити цю заявку');
        }

        $order->update(['status' => 'completed']);

        return redirect()->route('worker.orders.index')
            ->with('success', 'Заявку успішно завершено');
    }

    /**
     * Скасування заявки (тільки для воркера)
     */
    public function cancel(Order $order)
    {
        //  Перевіряємо: чи може воркер скасувати цю заявку?
        Gate::authorize('cancel', $order);

        // 🔒 Бізнес-логіка: тільки заявки "в роботі" можна скасовувати
        if ($order->status !== 'in_progress' || $order->worker_id !== auth()->id()) {
            return redirect()->back()->with('error', 'Ви не можете скасувати цю заявку');
        }

        $order->update([
            'worker_id' => null,
            'status' => 'new',
        ]);

        return redirect()->route('worker.orders.index')
            ->with('success', 'Виконання заявки скасовано. Заявка знову доступна');
    }
}
