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
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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
        Gate::authorize('take', $order);

        if ($order->status !== 'new' || $order->worker_id !== null) {
            return redirect()->back()->with('error', 'Ця заявка вже не доступна.');
        }

        $client = $order->client;
        $price = $order->price ?? 0;

        if ($price > 0 && !$client->hasBalance($price)) {
            return redirect()->back()->with('error', 'У клієнта недостатньо коштів для оплати заявки.');
        }

        if ($price > 0) {
            $client->withdraw($price, $order->id, "Оплата заявки #{$order->id}");
        }

        $action->execute($order);

        return redirect()->route('worker.orders.index')->with('success', 'Заявку взято в роботу.');
    }

    /**
     * Завершити заявку (тільки для воркера, який взяв її)
     */
    public function complete(Order $order, CompleteOrderAction $action)
    {
        Gate::authorize('complete', $order);

        if ($order->status !== 'in_progress' || $order->worker_id !== auth()->id()) {
            return redirect()->back()->with('error', 'Ви не можете завершити цю заявку.');
        }

        $worker = $order->worker;
        $price = $order->price ?? 0;

        if ($price > 0 && $worker) {
            $worker->deposit($price, null, "Оплата заявки #{$order->id}");
        }

        $action->execute($order);

        return redirect()->route('worker.orders.index')->with('success', 'Заявку успішно завершено!');
    }

    /**
     * Скасувати заявку (тільки для воркера, який взяв її)
     */
    public function cancel(Order $order, CancelOrderAction $action)
    {
        Gate::authorize('cancel', $order);

        if ($order->status !== 'in_progress' || $order->worker_id !== auth()->id()) {
            return redirect()->back()->with('error', 'Ви не можете скасувати цю заявку.');
        }

        $client = $order->client;
        $price = $order->price ?? 0;

        if ($price > 0 && $client) {
            $transaction = Transaction::where('order_id', $order->id)
                ->where('type', 'hold')
                ->where('status', 'completed')
                ->first();

            if ($transaction) {
                $client->deposit($price, null, "Повернення коштів за заявку #{$order->id}");
            }
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
        \Log::info('=== ORDER STORE START ===');
        \Log::info('Request all:', $request->all());
        \Log::info('Request files:', array_keys($request->allFiles()));
        \Log::info('Has photos: ' . ($request->hasFile('photos') ? 'YES' : 'NO'));
        \Log::info('Has files: ' . ($request->hasFile('files') ? 'YES' : 'NO'));

        if ($request->price > 0 && !auth()->user()->hasBalance($request->price)) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Поповніть баланс перед створенням заявки');
        }

        $data = $request->validated();

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            \Log::info('Processing photos...');
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('orders/photos', 's3');
                $photoPaths[] = $path;
                \Log::info('Photo saved to S3: ' . $path);
            }
        } else {
            \Log::info('No photos in request');
        }
        $data['photos'] = !empty($photoPaths) ? json_encode($photoPaths) : null;

        $filePaths = [];
        if ($request->hasFile('files')) {
            \Log::info('Processing files...');
            foreach ($request->file('files') as $file) {
                $path = $file->store('orders/files', 's3');
                $filePaths[] = $path;
                \Log::info('File saved to S3: ' . $path);
            }
        } else {
            \Log::info('No files in request');
        }
        $data['files'] = !empty($filePaths) ? json_encode($filePaths) : null;

        \Log::info('Data before createOrder:', $data);

        $order = $this->orderService->createOrder($data);

        return redirect()->route('client.orders.index')->with('success', 'Заявка створена');
    }

    /**
     * Перегляд однієї заявки (публічний).
     */
    public function show(Order $order)
    {
        $order->load('tags', 'client', 'worker');

        $orderData = $order->toArray();

        // ✅ ПРИНУДИТЕЛЬНО декодируем JSON
        $photos = [];
        if (!empty($order->photos)) {
            $decoded = json_decode($order->photos, true);
            // Если после декодирования все еще строка — декодируем еще раз
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            $photos = is_array($decoded) ? $decoded : [];
        }

        $files = [];
        if (!empty($order->files)) {
            $decoded = json_decode($order->files, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            $files = is_array($decoded) ? $decoded : [];
        }

        $orderData['photos_urls'] = !empty($photos) ? array_map(function ($path) {
            return Storage::disk('s3')->url($path);
        }, $photos) : [];

        $orderData['files_urls'] = !empty($files) ? array_map(function ($path) {
            return Storage::disk('s3')->url($path);
        }, $files) : [];

        \Log::info('Order Show Data:', [
            'order_id' => $order->id,
            'photos_raw' => $order->photos,
            'photos_decoded' => $photos,
            'files_decoded' => $files,
            'photos_urls' => $orderData['photos_urls'],
            'files_urls' => $orderData['files_urls'],
        ]);

        return Inertia::render('Orders/Show', ['order' => $orderData]);
    }

    /**
     * Форма редагування заявки (клієнт).
     */
    public function edit(Order $order)
    {
        if (auth()->user()->role_id !== 3 || $order->client_id !== auth()->id()) {
            abort(403, 'Ви не можете редагувати цю заявку.');
        }

        if ($order->status !== 'new') {
            return redirect()->route('client.orders.index')->with('error', 'Можна редагувати тільки нові заявки');
        }

        $orderData = $order->load('tags')->toArray();

        $photos = !empty($order->photos) ? json_decode($order->photos, true) : [];
        $files = !empty($order->files) ? json_decode($order->files, true) : [];

        $orderData['photos_urls'] = !empty($photos) ? array_map(function ($path) {
            return Storage::disk('s3')->url($path);
        }, $photos) : [];

        $orderData['files_urls'] = !empty($files) ? array_map(function ($path) {
            return Storage::disk('s3')->url($path);
        }, $files) : [];

        return Inertia::render('Client/Orders/Edit', [
            'order' => $orderData,
            'tags' => Tag::all(),
        ]);
    }

    /**
     * Оновлення заявки (клієнт).
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $data = $request->validated();

        // ОБНОВЛЯЕМ ФОТО (добавляем новые к существующим)
        $existingPhotos = $order->photos ?? [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $existingPhotos[] = $photo->store('orders/photos', 's3');
            }
        }
        $data['photos'] = $existingPhotos;

        // ОБНОВЛЯЕМ ФАЙЛЫ (добавляем новые к существующим)
        $existingFiles = $order->files ?? [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $existingFiles[] = $file->store('orders/files', 's3');
            }
        }
        $data['files'] = $existingFiles;

        // Удаляем файлы, которые отмечены для удаления
        if ($request->has('removed_files')) {
            $removedFiles = $request->input('removed_files');
            foreach ($removedFiles as $path) {
                Storage::disk('s3')->delete($path);
                $data['files'] = array_values(array_diff($data['files'], [$path]));
            }
        }

        \Log::info('Order updated with files:', [
            'order_id' => $order->id,
            'photos' => $data['photos'],
            'files' => $data['files'],
        ]);

        $this->orderService->updateOrder($order, $data);

        return redirect()->route('client.orders.index')->with('success', 'Заявка оновлена');
    }

    /**
     * Видалення заявки.
     */
    public function destroy(Order $order)
    {
        if (auth()->user()->role_id !== 3 || $order->client_id !== auth()->id()) {
            abort(403, 'Ви не можете видалити цю заявку.');
        }

        if ($order->status !== 'new') {
            return redirect()->route('orders.index')->with('error', 'Можна видаляти тільки нові заявки');
        }

        try {
            // Удаляем все фото из S3
            if (!empty($order->photos)) {
                foreach ($order->photos as $photo) {
                    Storage::disk('s3')->delete($photo);
                }
            }

            // Удаляем все файлы из S3
            if (!empty($order->files)) {
                foreach ($order->files as $file) {
                    Storage::disk('s3')->delete($file);
                }
            }

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
