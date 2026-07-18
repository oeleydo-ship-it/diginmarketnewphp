<?php
use App\Services\Gateways\BankTransferGateway;
use App\Services\Gateways\PayPalCheckoutGateway;
use App\Services\Gateways\PaystackCheckoutGateway;
use App\Services\Gateways\RazorpayCheckoutGateway;
use App\Services\Gateways\StripeCheckoutGateway;

// Checkout drivers. A gateway is offered at checkout only when it is enabled in
// admin settings, holds credentials, and supports the cart currency. An empty
// `currencies` list means the driver accepts whatever the marketplace charges.
return [
 'default'=>env('PAYMENTS_DEFAULT','stripe'),
 // Currencies billed in whole units, where the minor-unit conversion is x1 rather than x100.
 'zero_decimal_currencies'=>['BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'],
 'gateways'=>[
  'stripe'=>['label'=>'Card','driver'=>StripeCheckoutGateway::class,'currencies'=>[],'description'=>'Visa, Mastercard, Amex and local cards via Stripe.'],
  'paypal'=>['label'=>'PayPal','driver'=>PayPalCheckoutGateway::class,'currencies'=>['USD','EUR','GBP','AUD','CAD','JPY','CHF','SEK','SGD','HKD','NZD','MXN','BRL','PLN','DKK','NOK','CZK','HUF','ILS','PHP','TWD','THB'],'description'=>'Pay with a PayPal balance, card or bank.'],
  'razorpay'=>['label'=>'Razorpay','driver'=>RazorpayCheckoutGateway::class,'currencies'=>['INR'],'description'=>'UPI, netbanking, wallets and cards for India.'],
  'paystack'=>['label'=>'Paystack','driver'=>PaystackCheckoutGateway::class,'currencies'=>['NGN','GHS','ZAR','KES','USD'],'description'=>'Cards, bank transfer and mobile money for Africa.'],
  'mollie'=>['label'=>'Mollie','driver'=>\App\Services\Gateways\MollieCheckoutGateway::class,'currencies'=>['EUR','USD','GBP','CHF','SEK','NOK','DKK','PLN','CZK','HUF','AUD','CAD'],'description'=>'iDEAL, Bancontact, SEPA, cards and more for Europe.'],
  'flutterwave'=>['label'=>'Flutterwave','driver'=>\App\Services\Gateways\FlutterwaveCheckoutGateway::class,'currencies'=>['NGN','GHS','KES','UGX','TZS','RWF','ZAR','XAF','XOF','USD','EUR','GBP'],'description'=>'Cards, bank and mobile money across Africa.'],
  'instamojo'=>['label'=>'Instamojo','driver'=>\App\Services\Gateways\InstamojoCheckoutGateway::class,'currencies'=>['INR'],'description'=>'UPI, netbanking, wallets and cards for India.'],
  'sslcommerz'=>['label'=>'SslCommerz','driver'=>\App\Services\Gateways\SslCommerzCheckoutGateway::class,'currencies'=>['BDT','USD','EUR','GBP'],'description'=>'Cards, bKash, Nagad and banking for Bangladesh.'],
  'bank_transfer'=>['label'=>'Bank transfer','driver'=>BankTransferGateway::class,'currencies'=>[],'description'=>'Transfer manually; access unlocks once an administrator confirms receipt.'],
 ],
];
