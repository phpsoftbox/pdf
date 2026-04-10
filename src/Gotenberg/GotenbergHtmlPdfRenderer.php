<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf\Gotenberg;

use PhpSoftBox\Pdf\Exception\PdfRenderException;
use PhpSoftBox\Pdf\Internal\MultipartFormDataBuilder;
use PhpSoftBox\Pdf\PdfDocument;
use PhpSoftBox\Pdf\PdfMargins;
use PhpSoftBox\Pdf\PdfPageSize;
use PhpSoftBox\Pdf\PdfRendererInterface;
use PhpSoftBox\Pdf\PdfRenderOptions;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function rtrim;
use function sprintf;
use function strlen;
use function trim;

final readonly class GotenbergHtmlPdfRenderer implements PdfRendererInterface
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private string $baseUrl,
        private string $endpoint = '/forms/chromium/convert/html',
    ) {
    }

    public function renderHtml(string $html, ?PdfRenderOptions $options = null): PdfDocument
    {
        $resolvedOptions = $options ?? new PdfRenderOptions(
            pageSize: PdfPageSize::mm(210, 297),
            margins: PdfMargins::all(0),
        );

        $form = new MultipartFormDataBuilder();

        $form->addFile(
            name: 'files',
            filename: 'index.html',
            content: $html,
            contentType: 'text/html; charset=utf-8',
        );

        $this->appendChromiumFields($form, $resolvedOptions);

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
                'Failed to render PDF via Gotenberg. HTTP %d: %s',
                $status,
                $message !== '' ? $message : 'empty response body',
            ));
        }

        return new PdfDocument(
            content: (string) $response->getBody(),
            mimeType: $response->getHeaderLine('Content-Type') !== ''
                ? $response->getHeaderLine('Content-Type')
                : 'application/pdf',
        );
    }

    private function appendChromiumFields(MultipartFormDataBuilder $form, PdfRenderOptions $options): void
    {
        $form->addField('paperWidth', $this->formatDecimal($options->pageSize->widthInInches()));
        $form->addField('paperHeight', $this->formatDecimal($options->pageSize->heightInInches()));
        $form->addField('marginTop', $this->formatDecimal($options->margins->topInInches()));
        $form->addField('marginRight', $this->formatDecimal($options->margins->rightInInches()));
        $form->addField('marginBottom', $this->formatDecimal($options->margins->bottomInInches()));
        $form->addField('marginLeft', $this->formatDecimal($options->margins->leftInInches()));
        $form->addField('landscape', $options->landscape ? 'true' : 'false');
        $form->addField('printBackground', $options->printBackground ? 'true' : 'false');
        $form->addField('preferCssPageSize', $options->preferCssPageSize ? 'true' : 'false');
        $form->addField('scale', $this->formatDecimal($options->scale));
    }

    private function formatDecimal(float $value): string
    {
        return sprintf('%.6F', $value);
    }
}
