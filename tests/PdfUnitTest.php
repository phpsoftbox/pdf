<?php

declare(strict_types=1);

namespace PhpSoftBox\Pdf\Tests;

use PhpSoftBox\Pdf\PdfUnit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PdfUnitTest extends TestCase
{
    /**
     * Проверяет конвертацию миллиметров в дюймы.
     */
    #[Test]
    public function testMmToInches(): void
    {
        $this->assertEqualsWithDelta(1.0, PdfUnit::Mm->toInches(25.4), 0.000001);
    }

    /**
     * Проверяет конвертацию сантиметров в дюймы.
     */
    #[Test]
    public function testCmToInches(): void
    {
        $this->assertEqualsWithDelta(1.0, PdfUnit::Cm->toInches(2.54), 0.000001);
    }

    /**
     * Проверяет, что значение в дюймах не изменяется.
     */
    #[Test]
    public function testInchesToInches(): void
    {
        $this->assertSame(4.0, PdfUnit::In->toInches(4.0));
    }
}
