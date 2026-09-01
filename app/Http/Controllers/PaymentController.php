<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\LiqpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentController extends Controller
{
    protected LiqpayService $liqpayService;

    public function __construct(LiqpayService $liqpayService)
    {
        $this->liqpayService = $liqpayService;
    }

    /**
     * Поповнити баланс через LiqPay
     */
    public function deposit(DepositRequest $request)
    {
        \Log::info('=== DEPOSIT START ===');
        \Log::info('User: ' . auth()->id());
        \Log::info('Amount: ' . $request->amount);

        try {
            $user = auth()->user();
            $amount = $request->amount;
            $orderId = 'deposit_' . uniqid();

            \Log::info('Step 1: User found', ['user_id' => $user->id]);

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $amount,
                'balance_after' => $user->balance ?? 0,
                'status' => 'pending',
                'payment_id' => $orderId,
                'description' => 'Поповнення балансу через LiqPay',
            ]);

            \Log::info('Step 2: Transaction created', ['transaction_id' => $transaction->id]);

            $paymentUrl = $this->liqpayService->createPayment(
                $amount,
                "Поповнення балансу (#{$transaction->id})",
                $orderId
            );

            \Log::info('Step 3: Payment URL generated', ['url' => $paymentUrl]);

            return response()->json(['redirect_url' => $paymentUrl]);

        } catch (\Exception $e) {
            \Log::error('DEPOSIT ERROR: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['message' => 'Помилка: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Callback (аналог webhook) от LiqPay
     */
    public function callback(Request $request)
    {
        // ДОБАВЛЕНО: логируем входящий запрос
        \Log::info('=== LIQPAY CALLBACK HIT ===', [
            'method' => $request->method(),
            'ip' => $request->ip(),
            'all' => $request->all()
        ]);

        $data = $request->all();

        \Log::info('LiqPay callback received', ['data' => $data]);

        // 1. Проверяем подпись
        if (!$this->liqpayService->verifySignature($data)) {
            \Log::error('LiqPay callback: Invalid signature');
            return response()->json(['status' => 'error'], 400);
        }

        // 2. Извлекаем data из callback
        if (!isset($data['data'])) {
            \Log::error('LiqPay callback: Missing data field');
            return response()->json(['status' => 'error'], 400);
        }

        $decodedData = json_decode(base64_decode($data['data']), true);

        if (!$decodedData || !isset($decodedData['order_id'])) {
            \Log::error('LiqPay callback: Invalid data format');
            return response()->json(['status' => 'error'], 400);
        }

        // 3. Проверяем статус
        if (!in_array($decodedData['status'], ['success', 'sandbox'])) {
            \Log::warning('LiqPay callback: Invalid status', ['status' => $decodedData['status']]);
            return response()->json(['status' => 'ok']);
        }

        // 4. Обновляем транзакцию
        $transaction = Transaction::where('payment_id', $decodedData['order_id'])->first();

        if (!$transaction) {
            \Log::warning('Transaction not found', ['order_id' => $decodedData['order_id']]);
            return response()->json(['status' => 'ok']);
        }

        if ($transaction->status === 'completed') {
            return response()->json(['status' => 'ok']);
        }

        \Log::info('=== DEPOSIT COMPLETED ===', [
            'transaction_id' => $transaction->id,
            'user_id' => $transaction->user_id,
            'amount' => $transaction->amount,
            'status' => $decodedData['status']
        ]);

        $transaction->update(['status' => 'completed']);

        $user = User::find($transaction->user_id);
        if ($user) {
            $user->deposit($transaction->amount, $decodedData['order_id'], 'Поповнення через LiqPay');
        }

        return response()->json(['status' => 'ok']);
    }
}
