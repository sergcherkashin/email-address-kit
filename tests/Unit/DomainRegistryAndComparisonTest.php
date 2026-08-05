<?php

declare(strict_types=1);

namespace EmailAddressKit\Tests\Unit;

use EmailAddressKit\Domain\DomainRegistry;
use EmailAddressKit\Email;
use EmailAddressKit\EmailFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \EmailAddressKit\Domain\DomainRegistry
 * @covers \EmailAddressKit\Domain\Domain
 * @covers \EmailAddressKit\Comparison\DefaultComparisonStrategy
 */
final class DomainRegistryAndComparisonTest extends TestCase
{
    private EmailFactory $factory;

    protected function setUp(): void
    {
        $registry = new DomainRegistry();
        $registry->register(
            'gmail',
            'Gmail',
            ['gmail.com', 'googlemail.com'],
            ['gmail.com' => ['googlemail.com']]
        );
        $registry->register(
            'yandex',
            'Yandex',
            ['yandex.ru', 'ya.ru'],
            ['yandex.ru' => ['ya.ru']]
        );
        $registry->registerProvider('mailru', 'Mail.ru', [
            'mail.ru',
            'inbox.ru',
            'list.ru',
            'bk.ru',
        ]);

        $this->factory = EmailFactory::fromRegistry($registry);
        EmailFactory::setDefault($this->factory);
    }

    protected function tearDown(): void
    {
        EmailFactory::setDefault(null);
    }

    public function testServices(): void
    {
        self::assertSame('gmail', Email::parse('a@gmail.com')->service()->id());
        self::assertSame('gmail', Email::parse('a@googlemail.com')->service()->id());
        self::assertSame('yandex', Email::parse('a@ya.ru')->service()->id());
        self::assertSame('mailru', Email::parse('a@inbox.ru')->service()->id());
    }

    public function testEquivalentsAreEqual(): void
    {
        self::assertTrue(Email::parse('one@ya.ru')->equals('one@yandex.ru'));
        self::assertTrue(Email::parse('one@googlemail.com')->equals('one@gmail.com'));
    }

    public function testProviderDomainsAreNotEqual(): void
    {
        self::assertFalse(Email::parse('one@mail.ru')->equals('one@inbox.ru'));
        self::assertFalse(Email::parse('one@mail.ru')->equals('one@list.ru'));
        self::assertFalse(Email::parse('one@mail.ru')->equals('one@yandex.ru'));
    }

    public function testSameProviderAs(): void
    {
        $mail = Email::parse('one@mail.ru');
        $inbox = Email::parse('one@inbox.ru');
        $yandex = Email::parse('one@yandex.ru');

        self::assertTrue($mail->domain()->sameProviderAs($inbox->domain()));
        self::assertFalse($mail->domain()->sameProviderAs($yandex->domain()));
    }

    public function testCanonicalDefaults(): void
    {
        self::assertSame('mail.ru', Email::parse('a@mail.ru')->domain()->canonical());
        self::assertSame('inbox.ru', Email::parse('a@inbox.ru')->domain()->canonical());
        self::assertSame('yandex.ru', Email::parse('a@ya.ru')->domain()->canonical());
        self::assertSame('gmail.com', Email::parse('a@googlemail.com')->domain()->canonical());
    }

    public function testCaseInsensitiveEquals(): void
    {
        self::assertTrue(Email::parse('One@GMAIL.COM')->equals('one@gmail.com'));
    }

    public function testPlusTagSignificantByDefault(): void
    {
        self::assertFalse(Email::parse('john@gmail.com')->equals('john+test@gmail.com'));
        self::assertFalse(Email::parse('john@mail.ru')->equals('john+test@mail.ru'));
    }

    public function testPlusTagCanBeIgnoredViaFlag(): void
    {
        $options = \EmailAddressKit\Comparison\ComparisonOptions::ignorePlusTag();

        self::assertTrue(Email::parse('john@gmail.com')->equals('john+test@gmail.com', $options));
        self::assertTrue(Email::parse('john@mail.ru')->equals('john+test@mail.ru', $options));
    }

    public function testFilterEqualsPreservesValuesAndListKeys(): void
    {
        $matches = Email::parse('saval52281@ya.ru')->filterEquals([
            'ddd@yandex.ru',
            'SAVAL52281@yandex.ru',
            'saval52281@yandex.ru',
            'erg@mail.ru',
        ]);

        self::assertSame([
            1 => 'SAVAL52281@yandex.ru',
            2 => 'saval52281@yandex.ru',
        ], $matches);
    }

    public function testFilterEqualsPreservesAssociativeKeys(): void
    {
        $matches = Email::parse('saval52281@ya.ru')->filterEquals([
            10 => 'ddd@yandex.ru',
            11 => 'SAVAL52281@yandex.ru',
            36 => 'saval52281@yandex.ru',
            42 => 'erg@mail.ru',
        ]);

        self::assertSame([
            11 => 'SAVAL52281@yandex.ru',
            36 => 'saval52281@yandex.ru',
        ], $matches);
    }

    public function testFilterEqualsRespectsComparisonOptions(): void
    {
        $options = \EmailAddressKit\Comparison\ComparisonOptions::ignorePlusTag();

        $matches = Email::parse('john@ya.ru')->filterEquals([
            'john+news@yandex.ru',
            'other@yandex.ru',
        ], $options);

        self::assertSame([0 => 'john+news@yandex.ru'], $matches);
    }

    public function testCanonicalUsesEquivalentsAndLowercase(): void
    {
        self::assertSame(
            'saval52281@yandex.ru',
            Email::parse('SAVAL52281@ya.ru')->canonical()
        );
        self::assertSame(
            'saval52281@yandex.ru',
            Email::parse('saval52281@yandex.ru')->canonical()
        );
        self::assertSame(
            'one@gmail.com',
            Email::parse('One@googlemail.com')->canonical()
        );
    }

    public function testCanonicalDiffersFromNormalizedForEquivalents(): void
    {
        $email = Email::parse('SAVAL52281@ya.ru');

        self::assertSame('saval52281@ya.ru', $email->normalized());
        self::assertSame('saval52281@yandex.ru', $email->canonical());
    }

    public function testCanonicalRespectsIgnorePlusTag(): void
    {
        $options = \EmailAddressKit\Comparison\ComparisonOptions::ignorePlusTag();

        self::assertSame(
            'john@yandex.ru',
            Email::parse('john+news@ya.ru')->canonical($options)
        );
        self::assertSame(
            'john+news@yandex.ru',
            Email::parse('john+news@ya.ru')->canonical()
        );
    }

    public function testEqualsMatchesWhenCanonicalMatches(): void
    {
        $left = Email::parse('saval52281@ya.ru');
        $right = Email::parse('SAVAL52281@yandex.ru');

        self::assertSame($left->canonical(), $right->canonical());
        self::assertTrue($left->equals($right));
    }
}
