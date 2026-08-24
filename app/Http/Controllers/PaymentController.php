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

            // 1. Создаём транзакцию и СРАЗУ передаём payment_id
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $amount,
                'balance_after' => $user->balance ?? 0,
                'status' => 'pending',
                'payment_id' => $orderId, // ВАЖНО: присваиваем ID заказа
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
        try {
            $data = $request->all();

            // Проверяем подпись
            if (!$this->liqpayService->verifySignature($data)) {
                Log::warning('LiqPay: неверная подпись');
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            // Проверяем статус платежа
            if (!isset($data['status']) || $data['status'] !== 'success') {
                Log::warning('LiqPay: статус платежа не success', ['status' => $data['status'] ?? 'unknown']);
                return response()->json(['status' => 'ok']);
            }

            // Находим транзакцию по payment_id
            $transaction = Transaction::where('payment_id', $data['order_id'])->first();

            if (!$transaction) {
                Log::warning('LiqPay: транзакция не найдена', ['order_id' => $data['order_id'] ?? 'null']);
                return response()->json(['status' => 'ok']);
            }

            // Если транзакция уже завершена — ничего не делаем
            if ($transaction->status === 'completed') {
                return response()->json(['status' => 'ok']);
            }

            // Обновляем транзакцию и пополняем баланс
            $user = User::find($transaction->user_id);

            if ($user) {
                $user->deposit($transaction->amount, $data['order_id'], 'Поповнення через LiqPay');
                $transaction->update(['status' => 'completed']);
            }

            Log::info('LiqPay: callback обработан', [
                'order_id' => $data['order_id'],
                'amount' => $transaction->amount,
            ]);

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('LiqPay: ошибка callback', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
