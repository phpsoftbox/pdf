<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf;

interface PdfRendererInterface
{
    public function renderHtml(string $html, ?PdfRenderOptions $options = null): PdfDocument;
}
