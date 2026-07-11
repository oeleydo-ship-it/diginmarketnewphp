<?php
namespace App\Services;
use App\Contracts\PayoutGateway;
use App\Models\WithdrawalRequest;
use Stripe\StripeClient;
class StripePayoutGateway implements PayoutGateway {public function transfer(WithdrawalRequest $withdrawal,string $stripeAccountId,string $currency):array{$transfer=(new StripeClient((string)config('services.stripe.secret')))->transfers->create(['amount'=>(int)round((float)$withdrawal->net_amount*100),'currency'=>strtolower($currency),'destination'=>$stripeAccountId,'transfer_group'=>'withdrawal:'.$withdrawal->id,'metadata'=>['withdrawal_number'=>$withdrawal->number]],['idempotency_key'=>'withdrawal-transfer-'.$withdrawal->id]);return ['id'=>$transfer->id];}}
