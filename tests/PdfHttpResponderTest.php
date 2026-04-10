<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf\Tests;

use PhpSoftBox\Http\Message\ResponseFactory;
use PhpSoftBox\Http\Message\StreamFactory;
use PhpSoftBox\Pdf\Http\PdfHttpResponder;
use PhpSoftBox\Pdf\PdfDocument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PdfHttpResponderTest extends TestCase
{
    /**
     * Проверяет формирование HTTP-ответа для inline-просмотра PDF.
     */
    #[Test]
    public function testInlineResponse(): void
    {
        $responder = new PdfHttpResponder(new ResponseFactory(), new StreamFactory());

        $response = $responder->inline(new PdfDocument('%PDF-1.7'), 'label.pdf');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('inline', $response->getHeaderLine('Content-Disposition'));
    }

    /**
     * Проверяет формирование HTTP-ответа для скачивания PDF.
     */
    #[Test]
    public function testDownloadResponse(): void
    {
        $responder = new PdfHttpResponder(new ResponseFactory(), new StreamFactory());

        $response = $responder->download(new PdfDocument('%PDF-1.7'), 'export.pdf');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('attachment', $response->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString('export.pdf', $response->getHeaderLine('Content-Disposition'));
    }
}
