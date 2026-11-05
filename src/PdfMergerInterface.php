<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf;

/**
 * Склеивает несколько PDF в один документ в порядке списка.
 *
 * Нужен, когда документ слишком велик для одного рендера: его отрисовывают частями ({@see Batch\PdfChunkPlanner}) и
 * склеивают.
 */
interface PdfMergerInterface
{
    /**
     * @param list<PdfDocument> $documents Непустой список; один документ возвращается без изменений
     */
    public function merge(array $documents): PdfDocument;
}
