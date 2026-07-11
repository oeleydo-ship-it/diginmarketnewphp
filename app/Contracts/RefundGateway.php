<?php
namespace App\Contracts;
use App\Models\Payment;
interface RefundGateway {public function refund(Payment $payment,float $amount):array;}