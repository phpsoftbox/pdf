<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf\Tests;

use InvalidArgumentException;
use PhpSoftBox\Http\Message\RequestFactory;
use PhpSoftBox\Http\Message\Response;
use PhpSoftBox\Http\Message\StreamFactory;
use PhpSoftBox\Pdf\Exception\PdfRenderException;
use PhpSoftBox\Pdf\Gotenberg\GotenbergPdfMerger;
use PhpSoftBox\Pdf\PdfDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function strpos;

#[CoversClass(GotenbergPdfMerger::class)]
#[CoversMethod(GotenbergPdfMerger::class, 'merge')]
final class GotenbergPdfMergerTest extends TestCase
{
    /**
     * Проверяет, что части отправляются одним запросом на merge с именами по порядку и возвращается склеенный PDF.
     *
     * @see GotenbergPdfMerger::merge()
     */
    #[Test]
    public function mergeSendsPartsInOrderAndReturnsPdf(): void
    {
        $client = $this->client(new Response(200, ['Content-Type' => 'application/pdf'], '%PDF-merged'));

        $document = $this->merger($client)->merge([new PdfDocument('%PDF-a'), new PdfDocument('%PDF-b'), new PdfDocument('%PDF-c')]);

        $body = (string) $client->lastRequest?->getBody();
        self::assertSame('%PDF-merged', $document->content);
        self::assertSame('/forms/pdfengines/merge', $client->lastRequest?->getUri()->getPath());
        self::assertTrue(strpos($body, 'filename="00001.pdf"') < strpos($body, 'filename="00002.pdf"'));
        self::assertTrue(strpos($body, 'filename="00002.pdf"') < strpos($body, 'filename="00003.pdf"'));
    }

    /**
     * Проверяет, что один документ возвращается без запроса к Gotenberg.
     *
     * @see GotenbergPdfMerger::merge()
     */
    #[Test]
    public function singleDocumentIsReturnedWithoutRequest(): void
    {
        $client   = $this->client(new Response(500));
        $document = new PdfDocument('%PDF-single');

        self::assertSame([$document, null], [$this->merger($client)->merge([$document]), $client->lastRequest]);
    }

    /**
     * Проверяет, что пустой список отклоняется.
     *
     * @see GotenbergPdfMerger::merge()
     */
    #[Test]
    public function emptyListIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->merger($this->client(new Response(200)))->merge([]);
    }

    /**
     * Проверяет, что ошибка Gotenberg превращается в PdfRenderException с текстом ответа.
     *
     * @see GotenbergPdfMerger::merge()
     */
    #[Test]
    public function gotenbergErrorThrowsRenderException(): void
    {
        $this->expectException(PdfRenderException::class);
        $this->expectExceptionMessage('HTTP 503: timeout');

        $this->merger($this->client(new Response(503, [], 'timeout')))->merge([new PdfDocument('%PDF-a'), new PdfDocument('%PDF-b')]);
    }

    private function merger(ClientInterface $client): GotenbergPdfMerger
    {
        return new GotenbergPdfMerger(
            client: $client,
            requestFactory: new RequestFactory(),
            streamFactory: new StreamFactory(),
            baseUrl: 'http://gotenberg:3000',
        );
    }

    private function client(ResponseInterface $response): object
    {
        return new class ($response) implements ClientInterface {
            public ?RequestInterface $lastRequest = null;

            public function __construct(
                private readonly ResponseInterface $response,
            ) {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->lastRequest = $request;

                return $this->response;
            }
        };
    }
}
