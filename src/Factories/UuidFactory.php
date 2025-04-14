<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities\Factories;

use Exception;

class UuidFactory
{
    private $uuid;
    private $version = 4;

    public function generate(string $version = '4'): self
    {
        $this->version = (int)$version;
        
        // Generate 16 random bytes.
        $data = openssl_random_pseudo_bytes(16);
    
        switch ($this->version) {
            case 1:
                // Set the version to 0001 (version 1) and adjust the variant.
                $data[6] = chr(ord($data[6]) & 0x0f | 0x10);
                $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
                break;
            case 3:
                // Set the version to 0011 (version 3) and adjust the variant.
                $data[6] = chr(ord($data[6]) & 0x0f | 0x30);
                $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
                break;
            case 4:
                // Set the version to 0100 (version 4) and adjust the variant.
                $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
                $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
                break;
            case 5:
                // Set the version to 0101 (version 5) and adjust the variant.
                $data[6] = chr(ord($data[6]) & 0x0f | 0x50);
                $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
                break;
            default:
                throw new Exception("Unsupported UUID version: $version");
        }
    
        // Convert the random bytes to a UUID string format.
        $this->uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        
        return $this;
    }

    public function toString(): string
    {
        if (!$this->uuid) {
            $this->generate();
        }
        return $this->uuid;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function fromString(string $uuid): self
    {
        // Validate and store the UUID
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
            $this->uuid = $uuid;
        } else {
            throw new Exception("Invalid UUID format");
        }
        
        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}
