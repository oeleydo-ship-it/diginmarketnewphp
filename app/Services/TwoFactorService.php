<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Self-contained TOTP (RFC 6238) so 2FA needs no third-party package and no external network.
 * Secrets are base32; codes are 6 digits on a 30-second step verified against HMAC-SHA1.
 */
class TwoFactorService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // RFC 4648 base32.

    private const PERIOD = 30;

    private const DIGITS = 6;

    /** A fresh 160-bit base32 secret, the size RFC 4226 recommends for HMAC-SHA1. */
    public function generateSecret(): string
    {
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= self::ALPHABET[random_int(0, 31)];
        }

        return $secret;
    }

    /** otpauth:// URI an authenticator app imports (from a QR or manual paste). */
    public function provisioningUri(User $user, string $secret): string
    {
        $issuer = rawurlencode((string) config('app.name', 'DiginMarket'));
        $label = rawurlencode($issuer.':'.$user->email);

        return 'otpauth://totp/'.$label.'?secret='.$secret.'&issuer='.$issuer.'&algorithm=SHA1&digits='.self::DIGITS.'&period='.self::PERIOD;
    }

    /**
     * True when $code matches the secret. A ±1 step window absorbs clock skew between the
     * server and the user's phone without meaningfully widening the attack surface.
     */
    public function verify(string $secret, string $code, ?int $timestamp = null): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen((string) $code) !== self::DIGITS) {
            return false;
        }
        $counter = intdiv($timestamp ?? time(), self::PERIOD);
        for ($offset = -1; $offset <= 1; $offset++) {
            if (hash_equals($this->codeForCounter($secret, $counter + $offset), (string) $code)) {
                return true;
            }
        }

        return false;
    }

    /** The code the user's app is showing right now — handy for display and for tests. */
    public function currentCode(string $secret, ?int $timestamp = null): string
    {
        return $this->codeForCounter($secret, intdiv($timestamp ?? time(), self::PERIOD));
    }

    /** @return list<string> Ten single-use recovery codes for when the device is lost. */
    public function generateRecoveryCodes(): array
    {
        return Collection::times(10, fn () => Str::upper(Str::random(5).'-'.Str::random(5)))->all();
    }

    private function codeForCounter(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $binCounter = pack('N*', 0).pack('N*', $counter); // 64-bit big-endian counter.
        $hash = hash_hmac('sha1', $binCounter, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $truncated = (ord($hash[$offset]) & 0x7F) << 24
         | (ord($hash[$offset + 1]) & 0xFF) << 16
         | (ord($hash[$offset + 2]) & 0xFF) << 8
         | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($truncated % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $secret = rtrim(strtoupper($secret), '=');
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';
        foreach (str_split($secret) as $char) {
            $value = strpos(self::ALPHABET, $char);
            if ($value === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
