<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf;

enum PdfUnit: string
{
    case Mm = 'mm';
    case Cm = 'cm';
    case In = 'in';

    public function toInches(float $value): float
    {
        return match ($this) {
            self::Mm => $value / 25.4,
            self::Cm => $value / 2.54,
            self::In => $value,
        };
    }
}
