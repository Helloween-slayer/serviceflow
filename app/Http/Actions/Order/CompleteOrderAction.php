<?php

namespace App\Http\Actions\Order;

use App\Jobs\SendNotificationJob;
use App\Models\Order;

class CompleteOrderAction
{
    public function execute(Order $order): Order
    {
        $oldStatus = $order->status;

        $order->update([
            'status' => 'completed',
        ]);

        SendNotificationJob::dispatch($order, $oldStatus, $order->status);

        return $order;
    }
}
