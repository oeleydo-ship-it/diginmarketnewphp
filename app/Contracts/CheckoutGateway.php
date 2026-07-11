<?php
namespace App\Contracts;
use App\Models\Order;
interface CheckoutGateway {public function createCheckout(Order $order): array;}