<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities\Factories;

use DateTime;
use DateTimeZone;
use Exception;

class DateTimeFactory
{
    private $dateTime;

    public function __construct(string $datetime = null)
    {
        if ($datetime) {
            $this->dateTime = new DateTime($datetime);
        } else {
            $this->dateTime = new DateTime();
        }
    }

    public function format(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->dateTime->format($format);
    }

    public function timeAgo(): string
    {
        $now = new DateTime();
        $diff = $now->getTimestamp() - $this->dateTime->getTimestamp();

        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            return round($diff / 60) . ' minutes ago';
        } elseif ($diff < 86400) {
            return round($diff / 3600) . ' hours ago';
        } elseif ($diff < 604800) {
            return round($diff / 86400) . ' days ago';
        } elseif ($diff < 2592000) {
            return round($diff / 604800) . ' weeks ago';
        } elseif ($diff < 31536000) {
            return round($diff / 2592000) . ' months ago';
        } else {
            return round($diff / 31536000) . ' years ago';
        }
    }

    public function setTimezone(string $timezone): self
    {
        $this->dateTime->setTimezone(new DateTimeZone($timezone));
        return $this;
    }

    public function modify(string $modifier): self
    {
        $this->dateTime->modify($modifier);
        return $this;
    }

    public function diff(string $datetime): \DateInterval
    {
        $otherDate = new DateTime($datetime);
        return $this->dateTime->diff($otherDate);
    }

    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    public function toString(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->format($format);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
