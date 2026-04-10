<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf\Batch;

use InvalidArgumentException;

final class PdfChunkPlanner
{
    /**
     * @return list<PdfChunk>
     */
    public static function split(int $totalPages, int $pagesPerChunk): array
    {
        if ($totalPages < 1) {
            throw new InvalidArgumentException('Total pages must be >= 1.');
        }
        if ($pagesPerChunk < 1) {
            throw new InvalidArgumentException('Pages per chunk must be >= 1.');
        }

        $chunks = [];
        $index  = 1;
        $from   = 1;

        while ($from <= $totalPages) {
            $to = $from + $pagesPerChunk - 1;
            if ($to > $totalPages) {
                $to = $totalPages;
            }

            $chunks[] = new PdfChunk(
                index: $index,
                fromPage: $from,
                toPage: $to,
            );

            $index++;
            $from = $to + 1;
        }

        return $chunks;
    }
}
