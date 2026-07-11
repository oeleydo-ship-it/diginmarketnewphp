<?php
namespace App\Services;
use App\Contracts\RefundGateway;
use App\Models\Payment;
use Stripe\StripeClient;
class StripeRefundGateway implements RefundGateway {public function refund(Payment $payment,float $amount):array{$refund=(new StripeClient((string)config('services.stripe.secret')))->refunds->create(['payment_intent'=>$payment->provider_payment_id,'amount'=>(int)round($amount*100),'metadata'=>['order_id'=>(string)$payment->order_id]]);return ['id'=>$refund->id,'status'=>$refund->status];}}