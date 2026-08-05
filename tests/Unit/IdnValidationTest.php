<?php

declare(strict_types=1);

namespace EmailAddressKit\Tests\Unit;

use EmailAddressKit\Email;
use EmailAddressKit\EmailFactory;
use EmailAddressKit\Support\IdnConverter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \EmailAddressKit\Support\IdnConverter
 * @covers \EmailAddressKit\Validation\EmailValidator
 * @covers \EmailAddressKit\Domain\Domain
 * @covers \EmailAddressKit\Address\Address
 */
final class IdnValidationTest extends TestCase
{
    protected function setUp(): void
    {
        EmailFactory::setDefault(null);

        if (!\function_exists('idn_to_ascii')) {
            self::markTestSkipped('intl extension with idn_to_ascii() is required for IDN domain tests.');
        }
    }

    protected function tearDown(): void
    {
        EmailFactory::setDefault(null);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function eaiEmailProvider(): array
    {
        return [
            'cyrillic local + idn domain' => ['иван@почта.рф'],
            'cyrillic local + punycode domain' => ['иван@xn--80a1acny.xn--p1ai'],
            'cyrillic local + idn online tld' => ['инфо@компания.онлайн'],
            'ascii local + idn domain' => ['contact@почта.рф'],
            'cyrillic local + gmail' => ['иван.иванов@gmail.com'],
        ];
    }

    /**
     * @dataProvider eaiEmailProvider
     */
    public function testEaiAndIdnEmailsAreValid(string $input): void
    {
        $email = Email::parse($input);

        self::assertTrue($email->isValid(), $input . ' should be valid');
    }

    public function testUnicodeAndPunycodeDomainsAreEqual(): void
    {
        self::assertTrue(
            Email::parse('иван@почта.рф')->equals('иван@xn--80a1acny.xn--p1ai')
        );
    }

    public function testIdnDomainAsciiForm(): void
    {
        $email = Email::parse('contact@почта.рф');

        self::assertSame('почта.рф', $email->domain()->normalized());
        self::assertSame('xn--80a1acny.xn--p1ai', $email->domain()->ascii());
    }

    public function testNormalizedKeepsUnicodeLocalAndDomain(): void
    {
        $email = Email::parse('Иван@Почта.РФ');

        self::assertSame('иван@почта.рф', $email->normalized());
        self::assertSame('иван', $email->address()->normalized());
    }

    public function testLocalPartIsNotPunycoded(): void
    {
        $email = Email::parse('иван@gmail.com');

        self::assertSame('иван', $email->address()->normalized());
        self::assertTrue($email->isValid());
    }

    public function testConverterHandlesDomainsOnlyUseCase(): void
    {
        $converter = new IdnConverter();

        self::assertSame('gmail.com', $converter->toAscii('Gmail.COM'));
        self::assertSame('xn--80a1acny.xn--p1ai', $converter->toAscii('почта.рф'));
    }
}
