<?php

namespace App\Formatters;
class OrderDataFormatter
{
    public function formatOrders(array $orders)
    {
        $userProductMatrix = [];

        foreach ($orders as $order) {
            $userId = $order['user_id'];
            foreach ($order['food'] as $food) {
                $foodId = $food['id'];
                $quantity = $food['pivot']['quantity'];

                if (!isset($userProductMatrix[$userId])) {
                    $userProductMatrix[$userId] = [];
                }

                if (!isset($userProductMatrix[$userId][$foodId])) {
                    $userProductMatrix[$userId][$foodId] = 0;
                }

                $userProductMatrix[$userId][$foodId] += $quantity;
            }
        }

        return $userProductMatrix;
    }
}
