<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf;

use InvalidArgumentException;

final readonly class PdfMargins
{
    public function __construct(
        public float $top,
        public float $right,
        public float $bottom,
        public float $left,
        public PdfUnit $unit = PdfUnit::Mm,
    ) {
        if ($top < 0 || $right < 0 || $bottom < 0 || $left < 0) {
            throw new InvalidArgumentException('Margins must be non-negative.');
        }
    }

    public static function all(float $value, PdfUnit $unit = PdfUnit::Mm): self
    {
        return new self($value, $value, $value, $value, $unit);
    }

    public function topInInches(): float
    {
        return $this->unit->toInches($this->top);
    }

    public function rightInInches(): float
    {
        return $this->unit->toInches($this->right);
    }

    public function bottomInInches(): float
    {
        return $this->unit->toInches($this->bottom);
    }

    public function leftInInches(): float
    {
        return $this->unit->toInches($this->left);
    }
}
