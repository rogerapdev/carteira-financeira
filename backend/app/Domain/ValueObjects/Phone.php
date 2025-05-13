<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

class Phone
{
    private string $value;

    /**
     * @param string $phone
     * @throws InvalidArgumentException
     */
    public function __construct(string $phone)
    {
        $this->validate($phone);
        $this->value = $this->normalize($phone);
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Format phone number as (XX) XXXXX-XXXX.
     */
    public function format(): string
    {
        if (strlen($this->value) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $this->value);
        } elseif (strlen($this->value) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $this->value);
        }
        
        return $this->value;
    }

    /**
     * Normalize phone by removing non-digit characters.
     *
     * @param string $phone
     * @return string
     */
    private function normalize(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Validate phone format.
     *
     * @param string $phone
     * @throws InvalidArgumentException
     */
    private function validate(string $phone): void
    {
        $normalized = $this->normalize($phone);
        $length = strlen($normalized);

        if ($length < 10 || $length > 11) {
            throw new InvalidArgumentException(
                "Phone number must have 10 or 11 digits (DDD + number)."
            );
        }
    }
} 