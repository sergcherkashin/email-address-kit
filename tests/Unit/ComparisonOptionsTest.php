<?php

declare(strict_types=1);

namespace EmailAddressKit\Tests\Unit;

use EmailAddressKit\Comparison\ComparisonOptions;
use EmailAddressKit\Email;
use EmailAddressKit\EmailFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \EmailAddressKit\Comparison\DefaultComparisonStrategy
 * @covers \EmailAddressKit\Comparison\ComparisonOptions
 */
final class ComparisonOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        EmailFactory::setDefault(null);
    }

    protected function tearDown(): void
    {
        EmailFactory::setDefault(null);
    }

    public function testDefaultKeepsDotsAndPlusTag(): void
    {
        self::assertFalse(Email::parse('ser.g@gmail.com')->equals('serg@gmail.com'));
        self::assertFalse(Email::parse('serg+news@gmail.com')->equals('serg@gmail.com'));
        self::assertFalse(Email::parse('serg+news@mail.ru')->equals('serg@mail.ru'));
    }

    public function testIgnorePlusTagForAllProviders(): void
    {
        $options = ComparisonOptions::ignorePlusTag();

        self::assertTrue(Email::parse('serg+news@gmail.com')->equals('serg@gmail.com', $options));
        self::assertTrue(Email::parse('serg+news@mail.ru')->equals('serg@mail.ru', $options));
        self::assertTrue(Email::parse('serg+news@yandex.ru')->equals('serg@yandex.ru', $options));
    }

    public function testIgnoreGmailDots(): void
    {
        $options = ComparisonOptions::ignoreGmailDots();

        self::assertTrue(Email::parse('ser.g@gmail.com')->equals('serg@gmail.com', $options));
        self::assertTrue(Email::parse('s.e.r.g@gmail.com')->equals('serg@gmail.com', $options));
        self::assertTrue(Email::parse('Ser.G@Gmail.com')->equals('serg@googlemail.com', $options));
    }

    public function testIgnoreGmailDotsKeepsPlusTagByDefault(): void
    {
        $options = ComparisonOptions::ignoreGmailDots();

        self::assertFalse(Email::parse('serg+promo@gmail.com')->equals('serg@gmail.com', $options));
        self::assertTrue(Email::parse('ser.g+promo@gmail.com')->equals('serg+promo@gmail.com', $options));
    }

    public function testArrayOfFlagsMerges(): void
    {
        $options = [
            ComparisonOptions::ignoreGmailDots(),
            ComparisonOptions::ignorePlusTag(),
        ];

        self::assertTrue(Email::parse('ser.g@gmail.com')->equals('serg@gmail.com', $options));
        self::assertTrue(Email::parse('ser.g+news@gmail.com')->equals('serg@gmail.com', $options));
        self::assertTrue(Email::parse('serg+news@mail.ru')->equals('serg@mail.ru', $options));
    }

    public function testIgnoreGmailDotsDoesNotAffectOtherProviders(): void
    {
        $options = ComparisonOptions::ignoreGmailDots();

        self::assertFalse(Email::parse('ser.g@mail.ru')->equals('serg@mail.ru', $options));
        self::assertTrue(Email::parse('one@ya.ru')->equals('one@yandex.ru', $options));
    }

    public function testIgnoreGmailDotsStillRequiresSameMailbox(): void
    {
        $options = ComparisonOptions::ignoreGmailDots();

        self::assertFalse(Email::parse('serg@gmail.com')->equals('serg@yahoo.com', $options));
        self::assertFalse(Email::parse('serg@gmail.com')->equals('serg@mail.ru', $options));
    }

    public function testNormalizedIsUnchangedByOptions(): void
    {
        $email = Email::parse('Ser.G+Tag@Gmail.Com');

        self::assertSame('ser.g+tag@gmail.com', $email->normalized());
        self::assertTrue($email->equals('ser.g+tag@gmail.com'));
        self::assertTrue($email->equals('serg@gmail.com', [
            ComparisonOptions::ignoreGmailDots(),
            ComparisonOptions::ignorePlusTag(),
        ]));
    }

    public function testCanonicalRespectsFlagsAndArray(): void
    {
        self::assertSame('ser.g@gmail.com', Email::parse('Ser.G@gmail.com')->canonical());
        self::assertSame(
            'serg@gmail.com',
            Email::parse('Ser.G@googlemail.com')->canonical(ComparisonOptions::ignoreGmailDots())
        );
        self::assertSame(
            'serg@gmail.com',
            Email::parse('ser.g+news@gmail.com')->canonical([
                ComparisonOptions::ignoreGmailDots(),
                ComparisonOptions::ignorePlusTag(),
            ])
        );
    }

    public function testResolveMergesFlags(): void
    {
        $resolved = ComparisonOptions::resolve([
            ComparisonOptions::ignorePlusTag(),
            ComparisonOptions::ignoreGmailDots(),
        ]);

        self::assertTrue($resolved->shouldIgnorePlusTag());
        self::assertTrue($resolved->shouldIgnoreGmailDots());
    }

    public function testResolveRejectsInvalidArrayItem(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ComparisonOptions::resolve(['not-an-option']);
    }
}
