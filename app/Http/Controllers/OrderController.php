<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Tag;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Публічний список — тільки доступні заявки (status = new, worker_id = null).
     */
    public function index()
    {
        $orders = OrderService::getAvailableOrders();
        return Inertia::render('Orders/Index', ['orders' => $orders]);
    }

    /**
     * Заявки поточного клієнта.
     */
    public function clientOrders(Request $request, OrderService $service)
    {
        $orders = $service->getClientOrders(auth()->id(), $request->status);
        return Inertia::render('Client/Orders/Index', [
            'orders' => $orders,
            'activeTab' => $request->status ?? 'active'
        ]);
    }

    /**
     * Заявки поточного воркера.
     */
    public function workerOrders(Request $request, OrderService $service)
    {
        $orders = $service->getWorkerOrders(auth()->id(), $request->status);
        return Inertia::render('Worker/Orders/Index', [
            'orders' => $orders,
            'activeTab' => $request->status ?? 'active'
        ]);
    }

    /**
     * Адмін-дашборд: статистика та останні заявки.
     */
    public function adminDashboard(OrderService $service)
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => $service->getStats(),
            'recentOrders' => $service->getRecentOrders(),
        ]);
    }

    /**
     * Адмін: список всіх заявок.
     */
    public function adminOrders(OrderService $service)
    {
        $orders = $service->getAllOrders();
        return Inertia::render('Admin/Orders/Index', ['orders' => $orders]);
    }

    /**
     * Взяти заявку в роботу (тільки для виконавця).
     */
    public function takeOrder(Order $order)
    {
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
     */
    public function create()
    {
        $tags = Tag::all();
        return Inertia::render('Client/Orders/Create', ['tags' => $tags]);
    }

    /**
     * Збереження заявки.
     */
    public function store(Request $request)
    {
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
     */
    public function show(Order $order)
    {
        $order->load('tags', 'client', 'worker');
        return Inertia::render('Orders/Show', ['order' => $order]);
    }

    /**
     * Форма редагування заявки (клієнт).
     */
    public function edit(Order $order)
    {
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
     */
    public function destroy(Order $order)
    {
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
}
