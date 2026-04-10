<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf;

final readonly class PdfDocument
{
    public function __construct(
        public string $content,
        public string $mimeType = 'application/pdf',
    ) {
    }
}
