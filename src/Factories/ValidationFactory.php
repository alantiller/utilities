<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities\Factories;

use DateTime;

class ValidationFactory
{
    private $errors = [];
    
    public function date(string $date, string $format = 'm/d/Y', bool $strict = true): bool
    {
        $dateTime = DateTime::createFromFormat($format, $date);
        if ($strict) {
            $errors = DateTime::getLastErrors();
            if (!empty($errors['warning_count'])) {
                $this->errors['date'] = 'Invalid date format';
                return false;
            }
        }
        
        if ($dateTime === false) {
            $this->errors['date'] = 'Invalid date';
            return false;
        }
        
        return true;
    }
    
    public function email(string $email): bool
    {
        $valid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        if (!$valid) {
            $this->errors['email'] = 'Invalid email address';
        }
        return $valid;
    }
    
    public function url(string $url): bool
    {
        $valid = filter_var($url, FILTER_VALIDATE_URL) !== false;
        if (!$valid) {
            $this->errors['url'] = 'Invalid URL';
        }
        return $valid;
    }
    
    public function ip(string $ip): bool
    {
        $valid = filter_var($ip, FILTER_VALIDATE_IP) !== false;
        if (!$valid) {
            $this->errors['ip'] = 'Invalid IP address';
        }
        return $valid;
    }
    
    public function required(string $value, string $fieldName): bool
    {
        $valid = !empty(trim($value));
        if (!$valid) {
            $this->errors[$fieldName] = 'This field is required';
        }
        return $valid;
    }
    
    public function minLength(string $value, int $length, string $fieldName): bool
    {
        $valid = strlen($value) >= $length;
        if (!$valid) {
            $this->errors[$fieldName] = "Minimum length is $length characters";
        }
        return $valid;
    }
    
    public function maxLength(string $value, int $length, string $fieldName): bool
    {
        $valid = strlen($value) <= $length;
        if (!$valid) {
            $this->errors[$fieldName] = "Maximum length is $length characters";
        }
        return $valid;
    }
    
    public function numeric(string $value, string $fieldName): bool
    {
        $valid = is_numeric($value);
        if (!$valid) {
            $this->errors[$fieldName] = 'Value must be numeric';
        }
        return $valid;
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
    
    public function clearErrors(): self
    {
        $this->errors = [];
        return $this;
    }
}
