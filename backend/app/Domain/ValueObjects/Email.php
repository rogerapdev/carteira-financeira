<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

class Email
{
    private string $value;

    /**
     * @param string $email
     * @throws InvalidArgumentException
     */
    public function __construct(string $email)
    {
        $this->validate($email);
        $this->value = $email;
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
     * Validate email format.
     *
     * @param string $email
     * @throws InvalidArgumentException
     */
    private function validate(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format: {$email}");
        }
    }
} 