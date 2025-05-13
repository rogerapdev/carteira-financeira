<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

class Document
{
    private string $value;

    /**
     * @param string $document
     * @throws InvalidArgumentException
     */
    public function __construct(string $document)
    {
        $this->validate($document);
        $this->value = $this->normalize($document);
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
     * Format document as CPF (XXX.XXX.XXX-XX).
     */
    public function formatAsCpf(): string
    {
        if (strlen($this->value) !== 11) {
            return $this->value;
        }

        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->value);
    }

    /**
     * Format document as CNPJ (XX.XXX.XXX/XXXX-XX).
     */
    public function formatAsCnpj(): string
    {
        if (strlen($this->value) !== 14) {
            return $this->value;
        }

        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $this->value);
    }

    /**
     * Format document according to its length (CPF or CNPJ).
     */
    public function format(): string
    {
        return strlen($this->value) === 11 ? $this->formatAsCpf() : $this->formatAsCnpj();
    }

    /**
     * Normalize document by removing non-digit characters.
     *
     * @param string $document
     * @return string
     */
    private function normalize(string $document): string
    {
        return preg_replace('/[^0-9]/', '', $document);
    }

    /**
     * Validate document format (CPF or CNPJ).
     *
     * @param string $document
     * @throws InvalidArgumentException
     */
    private function validate(string $document): void
    {
        $normalized = $this->normalize($document);
        $length = strlen($normalized);

        if ($length !== 11 && $length !== 14) {
            throw new InvalidArgumentException("Document must have either 11 (CPF) or 14 (CNPJ) digits.");
        }

        // Simplified validation: Check if all digits are the same (which would be invalid)
        $allSameDigits = true;
        $firstDigit = $normalized[0];
        
        for ($i = 1; $i < $length; $i++) {
            if ($normalized[$i] !== $firstDigit) {
                $allSameDigits = false;
                break;
            }
        }

        if ($allSameDigits) {
            throw new InvalidArgumentException("Invalid document: all digits are the same.");
        }

        // Additional validations could be added here (checksum validation for CPF/CNPJ)
    }
} 