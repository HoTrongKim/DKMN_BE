<?php

namespace App\Services;

use App\Contracts\PaymentProviderInterface;
use App\Services\PaymentProviders\GenericQrProvider;
use InvalidArgumentException;

class PaymentProviderFactory
{
    public function getProvider(string $key): PaymentProviderInterface
    {
        $providers = config('payments.providers', []);
        $provider = $providers[$key] ?? null;

        if (!is_array($provider) || empty($provider['account'])) {
            throw new InvalidArgumentException("Unknown payment provider `{$key}`");
        }

        return new GenericQrProvider(
            $key,
            $provider['label'] ?? ucfirst($key),
            $provider['account']
        );
    }

    public function defaultProvider(): PaymentProviderInterface
    {
        return $this->getProvider(config('payments.default_provider', 'vietqr'));
    }
}
