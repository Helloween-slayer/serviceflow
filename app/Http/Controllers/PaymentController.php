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
        $data = $request->all();

        // Просто проверяем статус
        if ($data['status'] !== 'success') {
            return response()->json(['status' => 'ok']);
        }

        // Находим транзакцию
        $transaction = Transaction::where('payment_id', $data['order_id'])->first();

        if (!$transaction) {
            return response()->json(['status' => 'ok']);
        }

        // Обновляем транзакцию
        $transaction->update(['status' => 'completed']);
        $user = User::find($transaction->user_id);
        $user->deposit($transaction->amount, $data['order_id'], 'Поповнення через LiqPay');

        return response()->json(['status' => 'ok']);
    }
}
