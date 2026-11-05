<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf\Gotenberg;

use InvalidArgumentException;
use PhpSoftBox\Pdf\Exception\PdfRenderException;
use PhpSoftBox\Pdf\Internal\MultipartFormDataBuilder;
use PhpSoftBox\Pdf\PdfDocument;
use PhpSoftBox\Pdf\PdfMergerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function array_values;
use function count;
use function rtrim;
use function sprintf;
use function strlen;
use function trim;

/**
 * Склейка PDF через Gotenberg (`/forms/pdfengines/merge`). Gotenberg склеивает файлы в алфавитном порядке имён, поэтому
 * части называются по номеру с ведущими нулями: 00001.pdf, 00002.pdf…
 */
final readonly class GotenbergPdfMerger implements PdfMergerInterface
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private string $baseUrl,
        private string $endpoint = '/forms/pdfengines/merge',
    ) {
    }

    public function merge(array $documents): PdfDocument
    {
        $documents = array_values($documents);
        if ($documents === []) {
            throw new InvalidArgumentException('Nothing to merge: the list of PDF documents is empty.');
        }
        if (count($documents) === 1) {
            return $documents[0];
        }

        $form = new MultipartFormDataBuilder();

        foreach ($documents as $index => $document) {
            $form->addFile(
                name: 'files',
                filename: sprintf('%05d.pdf', $index + 1),
                content: $document->content,
                contentType: 'application/pdf',
            );
        }

        $body    = $form->build();
        $request = $this->requestFactory
            ->createRequest('POST', rtrim($this->baseUrl, '/') . $this->endpoint)
            ->withHeader('Accept', 'application/pdf')
            ->withHeader('Content-Type', $form->contentTypeHeader())
            ->withHeader('Content-Length', (string) strlen($body))
            ->withBody($this->streamFactory->createStream($body));

        $response = $this->client->sendRequest($request);
        $status   = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            $message = trim((string) $response->getBody());

            throw new PdfRenderException(sprintf(
                'Failed to merge PDF via Gotenberg. HTTP %d: %s',
                $status,
                $message !== '' ? $message : 'empty response body',
            ));
        }

        return new PdfDocument(content: (string) $response->getBody());
    }
}
