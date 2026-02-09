<?php

class JWT
{

    // 1. Generate Token (Sign In)
    public static function encode($payload, $secret)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    // 2. Validate Token (Middleware)
    public static function validate($token, $secret)
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        list($header, $payload, $signature) = $parts;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, $secret, true);
        $base64UrlValidSignature = self::base64UrlEncode($validSignature);

        if (!hash_equals($base64UrlValidSignature, $signature)) {
            return false;
        }

        // Decode Payload
        $decodedPayload = json_decode(self::base64UrlDecode($payload), true);

        // Check Expiry
        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return false; // Token Expired
        }

        return $decodedPayload;
    }

    // --- Helpers ---

    private static function base64UrlEncode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    // --- FIXED FUNCTION ---
    private static function base64UrlDecode($data)
    {
        $urlUnsafeData = str_replace(['-', '_'], ['+', '/'], $data);

        // Fix padding logic:
        $remainder = strlen($urlUnsafeData) % 4;
        if ($remainder) {
            $padLength = 4 - $remainder;
            $urlUnsafeData .= str_repeat('=', $padLength);
        }

        return base64_decode($urlUnsafeData);
    }
}
?>