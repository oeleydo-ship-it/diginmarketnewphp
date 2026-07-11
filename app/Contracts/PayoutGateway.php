<?php
namespace App\Contracts;
use App\Models\WithdrawalRequest;
interface PayoutGateway {public function transfer(WithdrawalRequest $withdrawal,string $stripeAccountId,string $currency):array;}
