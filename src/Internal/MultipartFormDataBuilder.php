<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf\Internal;

use function bin2hex;
use function random_bytes;
use function sprintf;

final class MultipartFormDataBuilder
{
    private string $boundary;
    /** @var list<array{name: string, value: string, filename: ?string, contentType: ?string}> */
    private array $parts = [];

    public function __construct(?string $boundary = null)
    {
        $this->boundary = $boundary ?? 'psb-' . bin2hex(random_bytes(16));
    }

    public function addField(string $name, string $value): self
    {
        $this->parts[] = [
            'name'        => $name,
            'value'       => $value,
            'filename'    => null,
            'contentType' => null,
        ];

        return $this;
    }

    public function addFile(string $name, string $filename, string $content, string $contentType): self
    {
        $this->parts[] = [
            'name'        => $name,
            'value'       => $content,
            'filename'    => $filename,
            'contentType' => $contentType,
        ];

        return $this;
    }

    public function boundary(): string
    {
        return $this->boundary;
    }

    public function contentTypeHeader(): string
    {
        return 'multipart/form-data; boundary=' . $this->boundary;
    }

    public function build(): string
    {
        $body = '';

        foreach ($this->parts as $part) {
            $body .= '--' . $this->boundary . "\r\n";

            $disposition = sprintf(
                'Content-Disposition: form-data; name="%s"',
                $part['name'],
            );
            if ($part['filename'] !== null) {
                $disposition .= sprintf('; filename="%s"', $part['filename']);
            }
            $body .= $disposition . "\r\n";

            if ($part['contentType'] !== null) {
                $body .= 'Content-Type: ' . $part['contentType'] . "\r\n";
            }

            $body .= "\r\n";
            $body .= $part['value'];
            $body .= "\r\n";
        }

        $body .= '--' . $this->boundary . "--\r\n";

        return $body;
    }
}
