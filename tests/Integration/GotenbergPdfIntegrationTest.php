<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf\Tests\Integration;

use PhpSoftBox\Http\Client\HttpClient;
use PhpSoftBox\Http\Message\RequestFactory;
use PhpSoftBox\Http\Message\ResponseFactory;
use PhpSoftBox\Http\Message\StreamFactory;
use PhpSoftBox\Pdf\Batch\PdfChunkPlanner;
use PhpSoftBox\Pdf\Gotenberg\GotenbergHtmlPdfRenderer;
use PhpSoftBox\Pdf\PdfRenderOptions;
use PhpSoftBox\Queue\Drivers\InMemoryDriver;
use PhpSoftBox\Queue\QueueJob;
use PhpSoftBox\Queue\Worker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

use function count;
use function file_put_contents;
use function getenv;
use function is_dir;
use function is_string;
use function mkdir;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function trim;

final class GotenbergPdfIntegrationTest extends TestCase
{
    private const DEFAULT_GOTENBERG_URL = 'http://gotenberg:3000';
    private const ARTIFACTS_DIR = __DIR__ . '/../../local/tests/pdf';

    /**
     * Проверяет интеграционную генерацию PDF через живой сервис Gotenberg.
     */
    #[Test]
    public function testRenderHtmlViaGotenberg(): void
    {
        $renderer = $this->createRendererIfAvailable();
        if (!$renderer instanceof GotenbergHtmlPdfRenderer) {
            return;
        }

        $document = $renderer->renderHtml(
            html: '<html><body><h1>Integration PDF</h1></body></html>',
            options: PdfRenderOptions::labelMm(58, 40, marginMm: 1),
        );

        $this->assertTrue(str_starts_with($document->content, '%PDF'));
        $this->assertTrue(str_contains($document->mimeType, 'application/pdf'));
        $this->savePdfArtifact('single-render', $document->content);
    }

    /**
     * Проверяет генерацию нескольких PDF-чанков через очередь и воркер.
     */
    #[Test]
    public function testRenderChunkedBatchViaQueueWorker(): void
    {
        $renderer = $this->createRendererIfAvailable();
        if (!$renderer instanceof GotenbergHtmlPdfRenderer) {
            return;
        }

        $chunks = PdfChunkPlanner::split(totalPages: 23, pagesPerChunk: 10);
        $queue  = new InMemoryDriver();

        foreach ($chunks as $chunk) {
            $queue->push(QueueJob::fromPayload([
                'chunk' => $chunk->index,
                'html'  => sprintf(
                    '<html><body><h1>Chunk %d</h1><p>Pages: %d-%d</p></body></html>',
                    $chunk->index,
                    $chunk->fromPage,
                    $chunk->toPage,
                ),
            ]));
        }

        $generated = [];
        $worker    = new Worker($queue, maxAttempts: 1);

        $processed = $worker->run(function (mixed $payload) use ($renderer, &$generated): void {
            $document = $renderer->renderHtml(
                html: (string) ($payload['html'] ?? ''),
                options: PdfRenderOptions::labelMm(58, 40, marginMm: 1),
            );
            $generated[] = $document->content;
        });

        $this->assertSame(count($chunks), $processed);
        $this->assertCount(count($chunks), $generated);
        foreach ($generated as $pdfBinary) {
            $this->assertTrue(str_starts_with($pdfBinary, '%PDF'));
        }
        foreach ($generated as $index => $pdfBinary) {
            $this->savePdfArtifact('chunk-' . (string) ($index + 1), $pdfBinary);
        }
    }

    private function createRendererIfAvailable(): ?GotenbergHtmlPdfRenderer
    {
        $baseUrl    = $this->resolveGotenbergUrl();
        $httpClient = $this->createHttpClient();

        try {
            $health = $httpClient->get($baseUrl . '/health');
            if ($health->getStatusCode() < 200 || $health->getStatusCode() >= 300) {
                $this->markTestSkipped('Gotenberg is not healthy (HTTP ' . $health->getStatusCode() . ').');
            }
        } catch (Throwable $exception) {
            $this->markTestSkipped('Gotenberg is unavailable: ' . $exception->getMessage());
        }

        return new GotenbergHtmlPdfRenderer(
            client: $httpClient,
            requestFactory: new RequestFactory(),
            streamFactory: new StreamFactory(),
            baseUrl: $baseUrl,
        );
    }

    private function createHttpClient(): HttpClient
    {
        return new HttpClient(
            responseFactory: new ResponseFactory(),
            streamFactory: new StreamFactory(),
            requestFactory: new RequestFactory(),
        );
    }

    private function resolveGotenbergUrl(): string
    {
        $value = getenv('GOTENBERG_URL');
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return self::DEFAULT_GOTENBERG_URL;
    }

    private function savePdfArtifact(string $name, string $content): void
    {
        if (!$this->artifactsEnabled()) {
            return;
        }

        if (!is_dir(self::ARTIFACTS_DIR)) {
            mkdir(self::ARTIFACTS_DIR, 0775, true);
        }

        $safeName = preg_replace('/[^a-z0-9\\-_]+/i', '-', $name);
        if (!is_string($safeName) || $safeName === '') {
            $safeName = 'artifact';
        }

        $filename = sprintf('%s/%s.pdf', self::ARTIFACTS_DIR, $safeName);
        file_put_contents($filename, $content);
    }

    private function artifactsEnabled(): bool
    {
        $value = getenv('PDF_TEST_SAVE_ARTIFACTS');
        if (!is_string($value)) {
            return false;
        }

        $normalized = trim($value);

        return $normalized === '1' || $normalized === 'true';
    }
}
