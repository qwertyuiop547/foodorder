<?php

require_once __DIR__ . '/../vendor/autoload.php';

class MqttPublisher
{
    public static function publishOrder(string $orderCode, string $status, string $customerName): bool
    {
        try {
            $mqtt = new \PhpMqtt\Client\MqttClient('127.0.0.1', 1883, 'food-order-system');
            $mqtt->connect(new \PhpMqtt\Client\ConnectionSettings(), true);
            
            $message = json_encode([
                'order_code' => $orderCode,
                'status' => $status,
                'customer' => $customerName,
                'time' => date('Y-m-d H:i:s')
            ]);
            
            $mqtt->publish("orders/{$orderCode}", $message, 0);
            $mqtt->publish('orders/all', $message, 0);
            $mqtt->disconnect();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}