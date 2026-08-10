<?php

namespace App\Http\Controllers;

use App\Http\Actions\Order\CancelOrderAction;
use App\Http\Actions\Order\CompleteOrderAction;
use App\Http\Actions\Order\TakeOrderAction;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Models\Order;
use App\Models\Tag;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Публічний список — тільки доступні заявки (status = new, worker_id = null).
     */
    public function index()
    {
        $orders = $this->orderService->getAvailableOrders();
        return Inertia::render('Orders/Index', ['orders' => $orders]);
    }

    /**
     * Заявки поточного клієнта.
     */
    public function clientOrders(Request $request)
    {
        $orders = $this->orderService->getClientOrders(auth()->id(), $request->status);
        return Inertia::render('Client/Orders/Index', [
            'orders' => $orders,
            'activeTab' => $request->status ?? 'active'
        ]);
    }

    /**
     * Заявки поточного воркера.
     */
    public function workerOrders(Request $request)
    {
        $orders = $this->orderService->getWorkerOrders(auth()->id(), $request->status);
        return Inertia::render('Worker/Orders/Index', [
            'orders' => $orders,
            'activeTab' => $request->status ?? 'active'
        ]);
    }

    /**
     * Адмін-дашборд: статистика та останні заявки.
     */
    public function adminDashboard()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => $this->orderService->getStats(),
            'recentOrders' => $this->orderService->getRecentOrders(),
        ]);
    }

    /**
     * Адмін: список всіх заявок.
     */
    public function adminOrders(Request $request)
    {
        $status = $request->input('status');

        if ($status && $status !== 'all') {
            $orders = $this->orderService->getAllOrders($status);
        } else {
            $orders = $this->orderService->getAllOrders();
        }

        return Inertia::render('Admin/Orders/Index', ['orders' => $orders]);
    }

    /**
     * Взяти заявку в роботу (тільки для виконавця).
     */
    public function takeOrder(Order $order, TakeOrderAction $action)
    {
        // Перевіряємо, що заявка доступна
        if ($order->status !== 'new' || $order->worker_id !== null) {
            return redirect()->back()->with('error', 'Ця заявка вже не доступна.');
        }

        $action->execute($order);

        return redirect()->route('worker.orders.index')->with('success', 'Заявку взято в роботу.');
    }

    /**
     * Завершити заявку (тільки для воркера, який взяв її)
     */
    public function complete(Order $order, CompleteOrderAction $action)
    {
        // Перевіряємо, що заявка в роботі у цього воркера
        if ($order->status !== 'in_progress' || $order->worker_id !== auth()->id()) {
            return redirect()->back()->with('error', 'Ви не можете завершити цю заявку.');
        }

        $action->execute($order);

        return redirect()->route('worker.orders.index')->with('success', 'Заявку успішно завершено!');
    }

    /**
     * Скасувати заявку (тільки для воркера, який взяв її)
     */
    public function cancel(Order $order, CancelOrderAction $action)
    {
        // Перевіряємо, що заявка в роботі у цього воркера
        if ($order->status !== 'in_progress' || $order->worker_id !== auth()->id()) {
            return redirect()->back()->with('error', 'Ви не можете скасувати цю заявку.');
        }

        $action->execute($order);

        return redirect()->route('worker.orders.index')->with('success', 'Виконання заявки скасовано.');
    }

    /**
     * Форма створення заявки.
     */
    public function create()
    {
        return Inertia::render('Client/Orders/Create', ['tags' => Tag::all()]);
    }

    /**
     * Збереження заявки.
     */
    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->createOrder($request->validated());

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
        // Перевіряємо, чи може клієнт редагувати цю заявку
        if (auth()->user()->role_id !== 3 || $order->client_id !== auth()->id()) {
            abort(403, 'Ви не можете редагувати цю заявку.');
        }

        if ($order->status !== 'new') {
            return redirect()->route('client.orders.index')->with('error', 'Можна редагувати тільки нові заявки');
        }

        return Inertia::render('Client/Orders/Edit', [
            'order' => $order->load('tags'),
            'tags' => Tag::all(),
        ]);
    }

    /**
     * Оновлення заявки (клієнт).
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $this->orderService->updateOrder($order, $request->validated());

        return redirect()->route('client.orders.index')->with('success', 'Заявка оновлена');
    }

    /**
     * Видалення заявки.
     */
    public function destroy(Order $order)
    {
        // Перевіряємо, чи може клієнт видалити цю заявку
        if (auth()->user()->role_id !== 3 || $order->client_id !== auth()->id()) {
            abort(403, 'Ви не можете видалити цю заявку.');
        }

        if ($order->status !== 'new') {
            return redirect()->route('orders.index')->with('error', 'Можна видаляти тільки нові заявки');
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
}
