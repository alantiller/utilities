<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities\Factories;

use Exception;

class StringFactory
{
    private $string = '';

    public function __construct(string $string = '')
    {
        $this->string = $string;
    }

    public function random(int $length = 10, string $charset = 'alphanumeric'): self
    {
        $characters = '';
        
        switch ($charset) {
            case 'numeric':
                $characters = '0123456789';
                break;
            case 'alpha':
                $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'alphanumeric':
            default:
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
        }
        
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        $this->string = $randomString;
        return $this;
    }

    public function slugify(): self
    {
        $this->string = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->string), '-'));
        return $this;
    }
    
    public function encrypt(string $key, string $method = 'aes-256-cbc'): self
    {
        $ivlen = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($this->string, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
        $this->string = base64_encode($iv . $hmac . $ciphertext_raw);
        
        return $this;
    }

    public function decrypt(string $key, string $method = 'aes-256-cbc'): self
    {
        $c = base64_decode($this->string);
        $ivlen = openssl_cipher_iv_length($method);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        $original = openssl_decrypt($ciphertext_raw, $method, $key, OPENSSL_RAW_DATA, $iv);
        
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
        if (!hash_equals($hmac, $calcmac)) {
            throw new Exception("HMAC validation failed");
        }
        
        $this->string = $original;
        return $this;
    }
    
    public function truncate(int $length = 100, string $append = '...'): self
    {
        if (strlen($this->string) > $length) {
            $this->string = substr($this->string, 0, $length) . $append;
        }
        
        return $this;
    }

    public function toString(): string
    {
        return $this->string;
    }

    public function __toString(): string
    {
        return $this->string;
    }
}
