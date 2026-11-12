<?php

namespace App\Models\Concerns;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

trait HasEncryptedRouteKey
{
    public function getRouteKey(): string
    {
        return static::encryptRouteKey((string) $this->getKey());
    }

    public static function encryptRouteKey(int|string $key): string
    {
        return rtrim(strtr(Crypt::encryptString((string) $key), '+/', '-_'), '=');
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if (! is_string($value) || ! preg_match('/\A[A-Za-z0-9_-]+\z/', $value)) {
            return null;
        }

        $encryptedValue = strtr($value, '-_', '+/');
        $encryptedValue .= str_repeat('=', (4 - strlen($encryptedValue) % 4) % 4);

        try {
            $key = Crypt::decryptString($encryptedValue);
        } catch (DecryptException) {
            return null;
        }

        if (! ctype_digit($key)) {
            return null;
        }

        return $this->resolveRouteBindingQuery($this, $key, $field)->first();
    }

    protected function routeKey(): Attribute
    {
        return Attribute::get(fn (): string => $this->getRouteKey());
    }
}
