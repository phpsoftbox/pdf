<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf\Tests;

use PhpSoftBox\Pdf\Batch\PdfChunkPlanner;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PdfChunkPlannerTest extends TestCase
{
    /**
     * Проверяет разбиение общего числа страниц на диапазоны чанков.
     */
    #[Test]
    public function testSplitProducesRanges(): void
    {
        $chunks = PdfChunkPlanner::split(totalPages: 1000, pagesPerChunk: 300);

        $this->assertCount(4, $chunks);
        $this->assertSame(1, $chunks[0]->fromPage);
        $this->assertSame(300, $chunks[0]->toPage);
        $this->assertSame(901, $chunks[3]->fromPage);
        $this->assertSame(1000, $chunks[3]->toPage);
    }
}
