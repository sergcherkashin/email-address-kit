<?php

declare(strict_types=1);

namespace EmailAddressKit\Tests\Integration;

use EmailAddressKit\Domain\BuiltinCompiledDomainSource;
use EmailAddressKit\Domain\DomainRegistry;
use EmailAddressKit\Email;
use EmailAddressKit\EmailFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \EmailAddressKit\Domain\BuiltinCompiledDomainSource
 * @covers \EmailAddressKit\EmailFactory
 */
final class BuiltinDomainDatasetTest extends TestCase
{
    protected function setUp(): void
    {
        EmailFactory::setDefault(null);
    }

    public function testCompiledDatasetLoads(): void
    {
        $registry = DomainRegistry::fromDataSource(BuiltinCompiledDomainSource::default());

        self::assertNotNull($registry->find('gmail.com'));
        self::assertNotNull($registry->find('googlemail.com'));
        self::assertNotNull($registry->find('ya.ru'));
        self::assertNotNull($registry->find('inbox.ru'));

        self::assertSame('gmail.com', $registry->canonical('googlemail.com'));
        self::assertSame('yandex.ru', $registry->canonical('ya.ru'));
        self::assertSame('mail.ru', $registry->canonical('mail.ru'));
        self::assertSame('inbox.ru', $registry->canonical('inbox.ru'));
    }

    public function testServicesMap(): void
    {
        $services = DomainRegistry::fromDataSource(BuiltinCompiledDomainSource::default())->services();

        self::assertSame('Gmail', $services['gmail']);
        self::assertSame('Yandex', $services['yandex']);
        self::assertSame('Mail.ru', $services['mailru']);
        $keys = \array_keys($services);
        $sorted = $keys;
        \sort($sorted);
        self::assertSame($sorted, $keys);
    }

    public function testPublicApiExampleFromTz(): void
    {
        $email = Email::parse('Vera.Coryacovskaya@Yndex.ru');

        self::assertSame('Vera.Coryacovskaya', $email->address()->value());
        self::assertSame('Yndex.ru', $email->domain()->value());
        self::assertSame('vera.coryacovskaya@yndex.ru', $email->normalized());
        self::assertTrue($email->isValid());
        self::assertNull($email->service());
        self::assertTrue($email->hasSuggestions());
        self::assertSame('vera.coryacovskaya@yandex.ru', $email->correct()->normalized());
    }

    public function testCompiledEntryOmitsDefaultCanonical(): void
    {
        $map = require \dirname(__DIR__, 2) . '/resources/compiled/domains.php';

        self::assertArrayHasKey('mail.ru', $map);
        self::assertArrayNotHasKey('c', $map['mail.ru']);
        self::assertArrayHasKey('c', $map['ya.ru']);
        self::assertSame('yandex.ru', $map['ya.ru']['c']);
    }
}
