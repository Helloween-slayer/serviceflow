<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WithdrawalController extends Controller
{
    public function index()
    {
        $withdrawals = Withdrawal::with('user')
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Withdrawals/Index', [
            'withdrawals' => $withdrawals,
        ]);
    }

    public function update(Request $request, Withdrawal $withdrawal)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,completed,rejected',
            'admin_note' => 'nullable|string',
        ]);

        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Ця заявка вже оброблена');
        }

        $user = $withdrawal->user;

        // Если отклоняем — возвращаем деньги
        if ($validated['status'] === 'rejected') {
            $user->deposit($withdrawal->amount, null, 'Повернення коштів після відхилення виводу #' . $withdrawal->id);
        }

        // Если завершаем — отмечаем как завершенный
        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        $withdrawal->update($validated);

        return redirect()->back()->with('success', 'Заявку оновлено!');
    }
}
