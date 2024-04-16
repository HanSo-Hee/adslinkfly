<?php

namespace App\Library;

class ExternalEncryptor
{
    private static $secret;
    private static $cipherMethod = 'AES-256-CBC';
    private static $separator = '::';
    private static $ivLength;

    private static function init($secret_key)
    {
        self::$secret = $secret_key;
        self::$ivLength = openssl_cipher_iv_length(self::$cipherMethod);
    }

    /**
     * @param string $data
     * @param string $secret_key
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function encrypt($data, $secret_key)
    {
        self::init($secret_key);

        $secret_key = hash('SHA256', self::$secret);
        $iv = openssl_random_pseudo_bytes(self::$ivLength);

        $ciphertext_raw = openssl_encrypt($data, self::$cipherMethod, $secret_key, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext_raw === false) {
            throw new \Exception();
        }

        $hmac = hash_hmac('sha256', $ciphertext_raw, $secret_key, true);

        return base64_encode($ciphertext_raw . self::$separator . $iv . self::$separator . $hmac);
    }

    /**
     * @param string $encoded_data
     * @param string $secret_key
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function decrypt($encoded_data, $secret_key)
    {
        self::init($secret_key);

        $data = base64_decode($encoded_data);
        if ($data === false) {
            throw new \Exception();
        }

        $secret_key = hash('SHA256', self::$secret);

        $data_array = explode(self::$separator, $data, 3);
        if (count($data_array) !== 3) {
            throw new \Exception();
        }

        [$ciphertext_raw, $iv, $hmac] = $data_array;

        $original_data = openssl_decrypt($ciphertext_raw, self::$cipherMethod, $secret_key, OPENSSL_RAW_DATA, $iv);
        if ($original_data === false) {
            throw new \Exception();
        }

        $calculated_hmac = hash_hmac('sha256', $ciphertext_raw, $secret_key, true);
        // timing attack safe comparison
        if (hash_equals($hmac, $calculated_hmac)) {
            return $original_data;
        }

        throw new \Exception();
    }
}
