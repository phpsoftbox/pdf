<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf\Batch;

use InvalidArgumentException;

final readonly class PdfChunk
{
    public function __construct(
        public int $index,
        public int $fromPage,
        public int $toPage,
    ) {
        if ($index < 1) {
            throw new InvalidArgumentException('Chunk index must be >= 1.');
        }
        if ($fromPage < 1 || $toPage < 1 || $fromPage > $toPage) {
            throw new InvalidArgumentException('Invalid page range.');
        }
    }

    public function pagesCount(): int
    {
        return $this->toPage - $this->fromPage + 1;
    }
}
