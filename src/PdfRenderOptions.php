<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf;

final readonly class PdfRenderOptions
{
    public function __construct(
        public PdfPageSize $pageSize,
        public PdfMargins $margins,
        public bool $landscape = false,
        public bool $printBackground = true,
        public bool $preferCssPageSize = false,
        public float $scale = 1.0,
    ) {
    }

    public static function labelMm(
        float $widthMm,
        float $heightMm,
        float $marginMm = 0.0,
    ): self {
        return new self(
            pageSize: PdfPageSize::mm($widthMm, $heightMm),
            margins: PdfMargins::all($marginMm, PdfUnit::Mm),
        );
    }
}
