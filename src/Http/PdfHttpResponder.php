<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf\Http;

use PhpSoftBox\Pdf\PdfDocument;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function is_string;
use function preg_replace;
use function rawurlencode;
use function strlen;
use function trim;

final readonly class PdfHttpResponder
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function inline(PdfDocument $document, string $filename = 'document.pdf'): ResponseInterface
    {
        return $this->createResponse($document, $filename, 'inline');
    }

    public function download(PdfDocument $document, string $filename = 'document.pdf'): ResponseInterface
    {
        return $this->createResponse($document, $filename, 'attachment');
    }

    private function createResponse(PdfDocument $document, string $filename, string $disposition): ResponseInterface
    {
        $safeFilename = $this->sanitizeFilename($filename);
        $content      = $document->content;

        return $this->responseFactory
            ->createResponse(200)
            ->withHeader('Content-Type', $document->mimeType !== '' ? $document->mimeType : 'application/pdf')
            ->withHeader('Content-Length', (string) strlen($content))
            ->withHeader(
                'Content-Disposition',
                $disposition . '; filename="' . $safeFilename . '"; filename*=UTF-8\'\'' . rawurlencode($safeFilename),
            )
            ->withHeader('Cache-Control', 'private, no-store, max-age=0')
            ->withBody($this->streamFactory->createStream($content));
    }

    private function sanitizeFilename(string $filename): string
    {
        $trimmed = trim($filename);
        if ($trimmed === '') {
            return 'document.pdf';
        }

        $clean = preg_replace('/[\\\\\\/\\r\\n\\t\\x00-\\x1F\\x7F]+/u', '_', $trimmed);
        if (!is_string($clean) || trim($clean) === '') {
            return 'document.pdf';
        }

        return $clean;
    }
}
