<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\LiqpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $user = auth()->user();
        $amount = $request->amount;
        $orderId = 'deposit_' . uniqid();

        // Создаем транзакцию со статусом pending
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => $amount,
            'balance_after' => $user->balance ?? 0,
            'status' => 'pending',
            'description' => 'Поповнення балансу через LiqPay',
        ]);

        // Создаем платеж в LiqPay
        $result = $this->liqpayService->createPayment(
            $amount,
            "Поповнення балансу (#{$transaction->id})",
            $orderId
        );

        // Сохраняем payment_id
        $transaction->update([
            'payment_id' => $result->order_id ?? null,
        ]);

        // Если LiqPay вернул ошибку
        if ($result->result !== 'ok') {
            $transaction->update(['status' => 'failed']);
            return back()->with('error', 'Помилка оплати: ' . ($result->err_description ?? 'не відома'));
        }

        // ✅ Оплата прошла — пополняем баланс
        $user->deposit($amount, $result->order_id, 'Поповнення через LiqPay');

        return redirect()->route('client.dashboard')->with('success', "Баланс поповнено на {$amount} грн!");
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
            if ($data['status'] !== 'success') {
                Log::warning('LiqPay: статус платежа не success', ['status' => $data['status']]);
                return response()->json(['status' => 'ok']);
            }

            // Находим транзакцию
            $transaction = Transaction::where('payment_id', $data['order_id'])->first();

            if (!$transaction) {
                Log::warning('LiqPay: транзакция не найдена', ['order_id' => $data['order_id']]);
                return response()->json(['status' => 'ok']);
            }

            // Проверяем, что транзакция еще не обработана
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
