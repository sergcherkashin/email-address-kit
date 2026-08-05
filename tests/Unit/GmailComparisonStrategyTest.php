<?php

declare(strict_types=1);

namespace EmailAddressKit\Tests\Unit;

use EmailAddressKit\Comparison\ComparisonOptions;
use EmailAddressKit\Comparison\DefaultComparisonStrategy;
use EmailAddressKit\Comparison\GmailComparisonStrategy;
use EmailAddressKit\Domain\BuiltinCompiledDomainSource;
use EmailAddressKit\Domain\DomainRegistry;
use EmailAddressKit\Email;
use EmailAddressKit\EmailFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \EmailAddressKit\Comparison\GmailComparisonStrategy
 * @covers \EmailAddressKit\Comparison\DefaultComparisonStrategy
 * @covers \EmailAddressKit\Comparison\ComparisonOptions
 */
final class GmailComparisonStrategyTest extends TestCase
{
    protected function tearDown(): void
    {
        EmailFactory::setDefault(null);
    }

    public function testDefaultStrategyKeepsDots(): void
    {
        $this->useStrategy(new DefaultComparisonStrategy());

        self::assertFalse(Email::parse('ser.g@gmail.com')->equals('serg@gmail.com'));
    }

    public function testDefaultStrategyKeepsPlusTagSignificant(): void
    {
        $this->useStrategy(new DefaultComparisonStrategy());

        self::assertFalse(Email::parse('serg+news@gmail.com')->equals('serg@gmail.com'));
        self::assertFalse(Email::parse('serg+news@mail.ru')->equals('serg@mail.ru'));
        self::assertFalse(Email::parse('serg+news@yandex.ru')->equals('serg@yandex.ru'));
    }

    public function testIgnorePlusTagFlagForAllProviders(): void
    {
        $this->useStrategy(new DefaultComparisonStrategy());
        $options = ComparisonOptions::ignorePlusTag();

        self::assertTrue(Email::parse('serg+news@gmail.com')->equals('serg@gmail.com', $options));
        self::assertTrue(Email::parse('serg+news@mail.ru')->equals('serg@mail.ru', $options));
        self::assertTrue(Email::parse('serg+news@yandex.ru')->equals('serg@yandex.ru', $options));
    }

    public function testGmailStrategyIgnoresDots(): void
    {
        $this->useStrategy(new GmailComparisonStrategy());

        self::assertTrue(Email::parse('ser.g@gmail.com')->equals('serg@gmail.com'));
        self::assertTrue(Email::parse('s.e.r.g@gmail.com')->equals('serg@gmail.com'));
        self::assertTrue(Email::parse('Ser.G@Gmail.com')->equals('serg@googlemail.com'));
    }

    public function testGmailStrategyKeepsPlusTagByDefault(): void
    {
        $this->useStrategy(new GmailComparisonStrategy());

        self::assertFalse(Email::parse('serg+promo@gmail.com')->equals('serg@gmail.com'));
        self::assertTrue(Email::parse('ser.g+promo@gmail.com')->equals('serg+promo@gmail.com'));
    }

    public function testGmailStrategyCanIgnorePlusTag(): void
    {
        $this->useStrategy(new GmailComparisonStrategy());
        $options = ComparisonOptions::ignorePlusTag();

        self::assertTrue(Email::parse('ser.g@gmail.com')->equals('serg@gmail.com', $options));
        self::assertTrue(Email::parse('ser.g+news@gmail.com')->equals('serg@gmail.com', $options));
        self::assertTrue(Email::parse('serg+news@mail.ru')->equals('serg@mail.ru', $options));
    }

    public function testGmailStrategyDoesNotIgnoreDotsForOtherProviders(): void
    {
        $this->useStrategy(new GmailComparisonStrategy());

        self::assertFalse(Email::parse('ser.g@mail.ru')->equals('serg@mail.ru'));
        self::assertTrue(Email::parse('one@ya.ru')->equals('one@yandex.ru'));
    }

    public function testGmailStrategyStillRequiresSameMailboxDomain(): void
    {
        $this->useStrategy(new GmailComparisonStrategy());

        self::assertFalse(Email::parse('serg@gmail.com')->equals('serg@yahoo.com'));
        self::assertFalse(Email::parse('serg@gmail.com')->equals('serg@mail.ru'));
    }

    public function testNormalizedIsUnchangedByStrategy(): void
    {
        $this->useStrategy(new GmailComparisonStrategy());

        $email = Email::parse('Ser.G+Tag@Gmail.Com');

        self::assertSame('ser.g+tag@gmail.com', $email->normalized());
        self::assertTrue($email->equals('ser.g+tag@gmail.com'));
        self::assertTrue($email->equals('serg@gmail.com', ComparisonOptions::ignorePlusTag()));
    }

    public function testGmailCanonicalStripsDots(): void
    {
        $this->useStrategy(new GmailComparisonStrategy());

        self::assertSame('serg@gmail.com', Email::parse('Ser.G@googlemail.com')->canonical());
        self::assertSame(
            'serg@gmail.com',
            Email::parse('ser.g+news@gmail.com')->canonical(ComparisonOptions::ignorePlusTag())
        );
    }

    public function testDefaultCanonicalKeepsGmailDots(): void
    {
        $this->useStrategy(new DefaultComparisonStrategy());

        self::assertSame('ser.g@gmail.com', Email::parse('Ser.G@gmail.com')->canonical());
    }

    
    private function useStrategy(?\EmailAddressKit\Comparison\EmailComparisonStrategyInterface $strategy): void
    {
        $registry = DomainRegistry::fromDataSource(BuiltinCompiledDomainSource::default());
        EmailFactory::setDefault(EmailFactory::fromRegistry($registry, $strategy));
    }
}
