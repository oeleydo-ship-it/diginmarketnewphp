<?php

namespace App\Services;

use App\Contracts\CheckoutGateway;
use App\Models\Setting;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string,CheckoutGateway> */
    private array $resolved = [];

    public function driver(?string $key = null): CheckoutGateway
    {
        $key = $key ?: (string) config('payments.default');
        $config = config('payments.gateways.'.$key);
        if (! $config) {
            throw new InvalidArgumentException('Unknown payment gateway ['.$key.'].');
        }

        return $this->resolved[$key] ??= app($config['driver']);
    }

    /** Admins switch gateways on per marketplace; Stripe stays on by default so upgrades keep working. */
    public function isEnabled(string $key): bool
    {
        return Setting::enabled('payments.'.$key.'.enabled', $key === (string) config('payments.default'));
    }

    /**
     * Gateways a buyer may actually choose: switched on, holding credentials, and able to bill the
     * cart currency. Filtering here is what stops a buyer reaching a dead redirect.
     *
     * @return array<string,array{label:string,description:string,gateway:CheckoutGateway}>
     */
    public function availableFor(string $currency): array
    {
        $available = [];
        foreach ((array) config('payments.gateways') as $key => $config) {
            if (! $this->isEnabled($key)) {
                continue;
            }
            $currencies = (array) ($config['currencies'] ?? []);
            if ($currencies !== [] && ! in_array(strtoupper($currency), $currencies, true)) {
                continue;
            }
            $gateway = $this->driver($key);
            if (! $gateway->isConfigured()) {
                continue;
            }
            $available[$key] = ['label' => $config['label'], 'description' => $config['description'] ?? '', 'gateway' => $gateway];
        }

        return $available;
    }

    /** @return list<string> */
    public function availableKeysFor(string $currency): array
    {
        return array_keys($this->availableFor($currency));
    }
}
