<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities;

use WebsiteSQL\Utilities\Factories\UuidFactory;
use WebsiteSQL\Utilities\Factories\StringFactory;
use WebsiteSQL\Utilities\Factories\DateTimeFactory;
use WebsiteSQL\Utilities\Factories\PaginationFactory;
use WebsiteSQL\Utilities\Factories\SecurityFactory;
use WebsiteSQL\Utilities\Factories\FileFactory;
use WebsiteSQL\Utilities\Factories\ValidationFactory;

class Utilities
{
    // Factory methods to create instances
    public static function uuid(): UuidFactory
    {
        return new UuidFactory();
    }
    
    public static function string(string $initialValue = ''): StringFactory
    {
        return new StringFactory($initialValue);
    }
    
    public static function datetime(string $datetime = null): DateTimeFactory
    {
        return new DateTimeFactory($datetime);
    }
    
    public static function pagination(array $data = []): PaginationFactory
    {
        return new PaginationFactory($data);
    }
    
    public static function security(): SecurityFactory
    {
        return new SecurityFactory();
    }
    
    public static function file(string $filePath = null): FileFactory
    {
        return new FileFactory($filePath);
    }
    
    public static function validation(): ValidationFactory
    {
        return new ValidationFactory();
    }
}