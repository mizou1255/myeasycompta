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
        
        // Générer une clé si elle n'existe pas ou est trop courte
        if (empty($this->key) || strlen($this->key) < 32) {
            $this->key = bin2hex(random_bytes(32)); // 64 caractères hex (32 bytes)
            update_option('ecwp_encryption_key', $this->key);
        }
        
        // Vérifier que la clé a la bonne longueur (minimum 32 caractères)
        if (strlen($this->key) < 32) {
            throw new \RuntimeException('Encryption key is too short. Minimum 32 characters required.');
        }
        
        $this->iv_length = openssl_cipher_iv_length($this->cipher);
        
        if ($this->iv_length === false) {
            throw new \RuntimeException('Failed to get IV length for cipher: ' . $this->cipher);
        }
    }

    public function encrypt($data)
    {
        // Retourner une chaîne vide si les données sont vides ou null
        if (empty($data) && $data !== '0' && $data !== 0) {
            return '';
        }
        
        $iv = openssl_random_pseudo_bytes($this->iv_length);
        if ($iv === false) {
            throw new \RuntimeException('Failed to generate IV for encryption.');
        }
        
        $encrypted = openssl_encrypt((string)$data, $this->cipher, $this->key, 0, $iv);
        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed. Check your encryption key and data.');
        }
        
        return base64_encode($encrypted . '::' . $iv);
    }

    public function decrypt($data)
    {
        // Retourner une chaîne vide si les données sont vides ou null
        if (empty($data) || $data === null) {
            return '';
        }
        
        $decoded_data = base64_decode($data, true);
        if ($decoded_data === false) {
            // Si le décodage base64 échoue, retourner la valeur originale (peut être du texte non crypté)
            return $data;
        }

        $parts = explode('::', $decoded_data, 2);

        if (count($parts) !== 2) {
            // Format invalide, retourner la valeur originale (peut être du texte non crypté)
            return $data;
        }

        list($encrypted_data, $iv) = $parts;
        
        // Vérifier que l'IV a la bonne longueur
        if (strlen($iv) !== $this->iv_length) {
            // IV invalide, retourner la valeur originale
            return $data;
        }

        $decrypted = openssl_decrypt($encrypted_data, $this->cipher, $this->key, 0, $iv);
        
        if ($decrypted === false) {
            // Échec du décryptage, retourner la valeur originale (peut être du texte non crypté)
            return $data;
        }
        
        return $decrypted;
    }
}
