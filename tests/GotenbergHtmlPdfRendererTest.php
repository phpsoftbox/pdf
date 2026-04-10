<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf\Tests;

use PhpSoftBox\Http\Message\RequestFactory;
use PhpSoftBox\Http\Message\Response;
use PhpSoftBox\Http\Message\StreamFactory;
use PhpSoftBox\Pdf\Gotenberg\GotenbergHtmlPdfRenderer;
use PhpSoftBox\Pdf\PdfMargins;
use PhpSoftBox\Pdf\PdfPageSize;
use PhpSoftBox\Pdf\PdfRenderOptions;
use PhpSoftBox\Pdf\PdfUnit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function str_contains;

final class GotenbergHtmlPdfRendererTest extends TestCase
{
    /**
     * Проверяет, что рендерер формирует корректный multipart-запрос и возвращает PDF-документ.
     */
    #[Test]
    public function testRenderHtmlBuildsMultipartRequestAndReturnsPdf(): void
    {
        $client = new class () implements ClientInterface {
            public ?RequestInterface $lastRequest = null;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->lastRequest = $request;

                return new Response(
                    200,
                    ['Content-Type' => 'application/pdf'],
                    '%PDF-mock',
                );
            }
        };

        $renderer = new GotenbergHtmlPdfRenderer(
            client: $client,
            requestFactory: new RequestFactory(),
            streamFactory: new StreamFactory(),
            baseUrl: 'http://gotenberg:3000',
        );

        $document = $renderer->renderHtml(
            '<html><body>Hello</body></html>',
            new PdfRenderOptions(
                pageSize: new PdfPageSize(58, 40, PdfUnit::Mm),
                margins: PdfMargins::all(1, PdfUnit::Mm),
            ),
        );

        $this->assertSame('%PDF-mock', $document->content);
        $this->assertSame('application/pdf', $document->mimeType);
        $this->assertNotNull($client->lastRequest);
        $this->assertSame(
            '/forms/chromium/convert/html',
            $client->lastRequest?->getUri()->getPath(),
        );
        $this->assertStringContainsString(
            'multipart/form-data',
            $client->lastRequest?->getHeaderLine('Content-Type') ?? '',
        );

        $body = (string) $client->lastRequest?->getBody();
        $this->assertTrue(str_contains($body, 'name="paperWidth"'));
        $this->assertTrue(str_contains($body, 'name="paperHeight"'));
        $this->assertTrue(str_contains($body, 'name="marginTop"'));
        $this->assertTrue(str_contains($body, 'name="files"; filename="index.html"'));
    }
}
