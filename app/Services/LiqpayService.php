<?php

namespace App\Services;

use LiqPay;

class LiqpayService
{
    protected LiqPay $liqpay;

    public function __construct()
    {
        $this->liqpay = new LiqPay(
            config('services.liqpay.public_key'),
            config('services.liqpay.private_key')
        );
    }

    /**
     * Створити платіж
     */
    public function createPayment(float $amount, string $description, string $orderId): object
    {
        $data = [
            'action' => 'pay',
            'amount' => $amount,
            'currency' => 'UAH',
            'description' => $description,
            'order_id' => $orderId,
            'version' => '3',
            'server_url' => config('services.liqpay.server_url'),
            'result_url' => route('client.dashboard'),
        ];

        return $this->liqpay->api('request', $data);
    }

    /**
     * Перевірити підпис callback
     */
    public function verifySignature(array $data): bool
    {
        return $this->liqpay->verifySignature($data);
    }
}
