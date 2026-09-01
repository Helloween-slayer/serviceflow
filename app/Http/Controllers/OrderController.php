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
        \Log::info('=== ORDER SHOW START ===');
        \Log::info('Order ID from route:', [
            'id' => $order->id,
            'type' => gettype($order->id),
            'order_object' => $order
        ]);

        $order->load('tags', 'client', 'worker');

        $orderData = $order->toArray();

        // ✅ Декодируем photos
        $photos = $this->decodeJsonField($order->photos);
        $files = $this->decodeJsonField($order->files);

        $orderData['photos'] = $photos;
        $orderData['files'] = $files;
        $orderData['photos_urls'] = !empty($photos) ? array_map(function ($path) {
            try {
                return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(60));
            } catch (\Exception $e) {
                \Log::error('Error generating temporary URL: ' . $e->getMessage());
                return null;
            }
        }, $photos) : [];

        $orderData['files_urls'] = !empty($files) ? array_map(function ($path) {
            try {
                return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(60));
            } catch (\Exception $e) {
                \Log::error('Error generating temporary URL: ' . $e->getMessage());
                return null;
            }
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

        $order->load('tags');

        $orderData = $order->toArray();
        $photos = $this->decodeJsonField($order->photos);
        $files = $this->decodeJsonField($order->files);

        $orderData['photos'] = $photos;
        $orderData['files'] = $files;
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

        // ✅ Получаем существующие фото (всегда в виде массива)
        $existingPhotos = $this->decodeJsonField($order->photos);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $existingPhotos[] = $photo->store('orders/photos', 's3');
            }
        }
        $data['photos'] = !empty($existingPhotos) ? json_encode($existingPhotos) : null;

        // ✅ Получаем существующие файлы (всегда в виде массива)
        $existingFiles = $this->decodeJsonField($order->files);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $existingFiles[] = $file->store('orders/files', 's3');
            }
        }

        // ✅ Удаляем файлы, которые отмечены для удаления
        if ($request->has('removed_files')) {
            $removedFiles = $request->input('removed_files');
            foreach ($removedFiles as $path) {
                Storage::disk('s3')->delete($path);
                $existingFiles = array_values(array_diff($existingFiles, [$path]));
            }
        }
        $data['files'] = !empty($existingFiles) ? json_encode($existingFiles) : null;

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
            // ✅ Удаляем все фото из S3
            $photos = $this->decodeJsonField($order->photos);
            if (!empty($photos)) {
                foreach ($photos as $photo) {
                    Storage::disk('s3')->delete($photo);
                }
            }

            // ✅ Удаляем все файлы из S3
            $files = $this->decodeJsonField($order->files);
            if (!empty($files)) {
                foreach ($files as $file) {
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

    /**
     * ✅ Вспомогательный метод для декодирования JSON полей
     * Всегда возвращает массив
     */
    private function decodeJsonField($value): array
    {
        if (empty($value)) {
            return [];
        }

        // Если уже массив
        if (is_array($value)) {
            return $value;
        }

        // Если строка - пробуем декодировать
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            // Если после декодирования все еще строка - декодируем еще раз
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
