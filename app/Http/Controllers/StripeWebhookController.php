<?php
namespace App\Http\Controllers;
use App\Services\StripeWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
class StripeWebhookController extends Controller {public function __invoke(Request $request,StripeWebhookService $service): Response{$service->handle($request->getContent(),(string)$request->header('Stripe-Signature'));return response('received');}}