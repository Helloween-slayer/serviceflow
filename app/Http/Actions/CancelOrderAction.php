<?php

namespace App\Http\Actions\Order;

use App\Jobs\SendNotificationJob;
use App\Models\Order;

class CancelOrderAction
{
    public function execute(Order $order): Order
    {
        $oldStatus = $order->status;

        $order->update([
            'worker_id' => null,
            'status' => 'new',
        ]);

        SendNotificationJob::dispatch($order, $oldStatus, $order->status);

        return $order;
    }
}
