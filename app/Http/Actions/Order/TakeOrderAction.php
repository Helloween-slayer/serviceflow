<?php

namespace App\Http\Actions\Order;

use App\Jobs\SendNotificationJob;
use App\Models\Order;

class TakeOrderAction
{
    public function execute(Order $order): Order
    {
        $oldStatus = $order->status;

        $order->update([
            'worker_id' => auth()->id(),
            'status' => 'in_progress',
        ]);

        SendNotificationJob::dispatch($order, $oldStatus, $order->status);

        return $order;
    }
}
