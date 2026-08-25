<?php

namespace App\Services;

class LiqpayService
{
    protected $liqpay;

    public function __construct()
    {
        $this->liqpay = new \LiqPay(
            config('services.liqpay.public_key'),
            config('services.liqpay.private_key')
        );
    }

    /**
     * Створити платеж в LiqPay (возвращает ссылку)
     */
    public function createPayment(float $amount, string $description, string $orderId): string
    {
        \Log::info('=== LIQPAY CREATE PAYMENT ===');
        \Log::info('Amount: ' . $amount);
        \Log::info('OrderId: ' . $orderId);

        $publicKey = config('services.liqpay.public_key');
        $privateKey = config('services.liqpay.private_key');

        \Log::info('Public key: ' . $publicKey);
        \Log::info('Private key length: ' . strlen($privateKey));

        $data = [
            'public_key' => $publicKey,
            'version' => '3',
            'action' => 'pay',
            'amount' => $amount,
            'currency' => 'UAH',
            'description' => $description,
            'order_id' => $orderId,
            'server_url' => config('services.liqpay.server_url'),
            'result_url' => config('services.liqpay.result_url'), 
            'sandbox' => 1,
        ];
        $encodedData = base64_encode(json_encode($data));
        $signature = base64_encode(sha1($privateKey . $encodedData . $privateKey, 1));

        $url = 'https://www.liqpay.ua/api/3/checkout?data=' . $encodedData . '&signature=' . $signature;

        \Log::info('Generated URL: ' . substr($url, 0, 200) . '...');

        return $url;
    }

    /**
     * Перевірити підпис callback
     */
    public function verifySignature(array $data): bool
    {
        return $this->liqpay->verifySignature($data);
    }
}
