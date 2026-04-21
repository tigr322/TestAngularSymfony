<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\Import\DiscountCalculator;
use PHPUnit\Framework\TestCase;

final class DiscountCalculatorTest extends TestCase
{
    public function testCalculatesDiscountPercent(): void
    {
        $calculator = new DiscountCalculator();

        self::assertSame('50.00', $calculator->calculate('1200.00', '800.00'));
    }

    public function testReturnsNullForMissingOrZeroPurchasePrice(): void
    {
        $calculator = new DiscountCalculator();

        self::assertNull($calculator->calculate('1200.00', null));
        self::assertNull($calculator->calculate('1200.00', '0.00'));
    }
}
