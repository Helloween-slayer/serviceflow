<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Публічний список — тільки доступні заявки (status = new, worker_id = null).
     */
    public function index()
    {
        $orders = Order::where('status', 'new')->whereNull('worker_id')->with('tags', 'client')->paginate(10);
        return Inertia::render('Orders/Index', ['orders' => $orders]);
    }

    /**
     * Мої заявки — тільки заявки поточного клієнта.
     */
    public function clientOrders(Request $request)
    {
        $query = Order::where('client_id', auth()->id())->with('tags');

        if ($request->status === 'completed') {
            $query->whereIn('status', ['completed', 'cancelled']);
        } else {
            $query->whereNotIn('status', ['completed', 'cancelled']);
        }

        $orders = $query->paginate(10);
        return Inertia::render('Client/Orders/Index', ['orders' => $orders, 'activeTab' => $request->status ?? 'active']);
    }

    /**
     * Мої заявки (для виконавця) — заявки, які взяв поточний воркер.
     */
    public function myWorkerOrders(Request $request)
    {
        $query = Order::where('worker_id', auth()->id())->with('tags', 'client');

        if ($request->status === 'completed') {
            $query->whereIn('status', ['completed', 'ready']);
        } else {
            $query->whereIn('status', ['in_progress', 'new']);
        }

        $orders = $query->paginate(10);
        return Inertia::render('Worker/Orders/Index', [
            'orders' => $orders,
            'activeTab' => $request->status ?? 'active'
        ]);
    }


    /**
     * Всі заявки для адміна (повний список, без фільтрів).
     */
    public function adminDashboard()
    {
        $stats = [
            'totalOrders' => Order::count(),
            'activeOrders' => Order::whereIn('status', ['new', 'in_progress'])->count(),
            'completedOrders' => Order::whereIn('status', ['completed', 'ready'])->count(),
            'totalUsers' => User::count(),
        ];

        $recentOrders = Order::with('client')
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }

    /**
     * Взяти заявку в роботу (тільки для виконавця).
     */
    public function takeOrder(Order $order)
    {
        // Перевіряємо, що заявка доступна
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
     * Перегляд однієї заявки.
     */
    public function show(Order $order)
    {
        $order->load('tags', 'client', 'worker');
        return Inertia::render('Orders/Show', ['order' => $order]);
    }

    /**
     * Форма редагування заявки.
     */
    public function edit(Order $order)
    {
        // $this->authorize('update', $order);
        $tags = Tag::all();
        return Inertia::render('Client/Orders/Edit', [
            'order' => $order->load('tags'),
            'tags' => $tags,
        ]);
    }

    /**
     * Оновлення заявки.
     */
    public function update(Request $request, Order $order)
    {
        // $this->authorize('update', $order);

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

            return redirect()->route('orders.index')
                ->with('success', 'Заявку успішно видалено');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Не вдалося видалити заявку: ' . $e->getMessage()
            ]);
        }
    }
}
