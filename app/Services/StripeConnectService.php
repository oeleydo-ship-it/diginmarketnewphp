<?php
namespace App\Services;
use App\Models\StripeConnectedAccount;
use App\Models\User;
use Stripe\StripeClient;
class StripeConnectService
{
 private function client():StripeClient{return new StripeClient((string)config('services.stripe.secret'));}
 public function onboarding(User $seller):string{$record=StripeConnectedAccount::where('seller_id',$seller->id)->first();if(!$record){$account=$this->client()->accounts->create(['type'=>'express','email'=>$seller->email,'metadata'=>['seller_id'=>(string)$seller->id]]);$record=StripeConnectedAccount::create(['seller_id'=>$seller->id,'stripe_account_id'=>$account->id]);}$link=$this->client()->accountLinks->create(['account'=>$record->stripe_account_id,'refresh_url'=>route('seller.connect.refresh'),'return_url'=>route('seller.connect.return'),'type'=>'account_onboarding']);return $link->url;}
 public function sync(User $seller):StripeConnectedAccount{$record=StripeConnectedAccount::where('seller_id',$seller->id)->firstOrFail();$account=$this->client()->accounts->retrieve($record->stripe_account_id,[]);$record->update(['status'=>$account->payouts_enabled?'enabled':($account->details_submitted?'restricted':'pending'),'details_submitted'=>$account->details_submitted,'charges_enabled'=>$account->charges_enabled,'payouts_enabled'=>$account->payouts_enabled,'requirements'=>$account->requirements?->toArray(),'last_synced_at'=>now()]);return $record;}
}