<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf;

use InvalidArgumentException;

final readonly class PdfPageSize
{
    public function __construct(
        public float $width,
        public float $height,
        public PdfUnit $unit = PdfUnit::Mm,
    ) {
        if ($width <= 0 || $height <= 0) {
            throw new InvalidArgumentException('Page size must be positive.');
        }
    }

    public static function mm(float $width, float $height): self
    {
        return new self($width, $height, PdfUnit::Mm);
    }

    public static function inches(float $width, float $height): self
    {
        return new self($width, $height, PdfUnit::In);
    }

    public function widthInInches(): float
    {
        return $this->unit->toInches($this->width);
    }

    public function heightInInches(): float
    {
        return $this->unit->toInches($this->height);
    }
}
