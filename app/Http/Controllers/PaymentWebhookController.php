<?php

namespace App\Http\Controllers;

use App\Services\PaymentWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentWebhookService $service): Response
    {
        abort_unless(array_key_exists($provider, (array) config('payments.gateways')), 404);
        // Signature checks need the exact bytes the provider signed, never a re-encoded array.
        $headers = collect($request->headers->all())->mapWithKeys(fn (array $values, string $name) => [strtolower($name) => (string) ($values[0] ?? '')])->all();
        $service->handle($provider, $request->getContent(), $headers);

        return response('received');
    }
}
