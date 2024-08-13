<?php

namespace ECWP\Admin\Encrypt;

class ECWP_Encrypt
{
    private $key;
    private $cipher = 'aes-256-cbc';
    private $iv_length;

    public function __construct()
    {
        $this->key = get_option('ecwp_encryption_key');
        $this->iv_length = openssl_cipher_iv_length($this->cipher);
    }

    public function encrypt($data)
    {
        $iv = openssl_random_pseudo_bytes($this->iv_length);
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    public function decrypt($data)
    {
        $decoded_data = base64_decode($data);

        if ($decoded_data === false) {
            return false;
        }

        $parts = explode('::', $decoded_data, 2);

        if (count($parts) !== 2) {
            return false;
        }

        list($encrypted_data, $iv) = $parts;

        return openssl_decrypt($encrypted_data, $this->cipher, $this->key, 0, $iv);
    }
}
