<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WithdrawalController extends Controller
{
    /**
     * Страница баланса с историей и выводом
     */
    public function index()
    {
        $user = auth()->user();

        // Транзакции пользователя
        $transactions = $user->transactions()
            ->latest()
            ->paginate(10);

        // Заявки на вывод
        $withdrawals = Withdrawal::where('user_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('Worker/Balance/Index', [
            'balance' => $user->balance,
            'transactions' => $transactions,
            'withdrawals' => $withdrawals,
        ]);
    }

    /**
     * Создать заявку на вывод
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:' . $user->balance,
            'payment_details' => 'required|string|min:16|max:19', // номер карты
        ]);

        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'amount' => $validated['amount'],
            'status' => 'pending',
            'payment_method' => 'bank',
            'payment_details' => $validated['payment_details'],
        ]);

        $user->withdraw($validated['amount'], null, 'Запит на виведення #' . $withdrawal->id);

        return redirect()->back()->with('success', 'Запит на виведення створено!');
    }
}
