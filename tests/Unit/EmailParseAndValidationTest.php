<?php

declare(strict_types=1);

namespace EmailAddressKit\Tests\Unit;

use EmailAddressKit\Email;
use EmailAddressKit\EmailFactory;
use EmailAddressKit\Validation\ValidationErrorCode;
use PHPUnit\Framework\TestCase;

/**
 * @covers \EmailAddressKit\Email
 * @covers \EmailAddressKit\EmailFactory
 * @covers \EmailAddressKit\Address\Address
 * @covers \EmailAddressKit\Domain\Domain
 * @covers \EmailAddressKit\Validation\EmailValidator
 */
final class EmailParseAndValidationTest extends TestCase
{
    protected function setUp(): void
    {
        EmailFactory::setDefault(null);
    }

    public function testParseValidEmail(): void
    {
        $email = Email::parse('user.name@gmail.com');

        self::assertSame('user.name', $email->address()->value());
        self::assertSame('gmail.com', $email->domain()->value());
        self::assertTrue($email->isValid());
    }

    public function testParsePlusAddressing(): void
    {
        $email = Email::parse('user+test@gmail.com');

        self::assertSame('user+test', $email->address()->value());
        self::assertTrue($email->isValid());
    }

    /**
     * @dataProvider invalidEmailProvider
     */
    public function testInvalidEmails(string $input, string $expectedCode): void
    {
        $email = Email::parse($input);

        self::assertFalse($email->isValid());

        $codes = [];
        foreach ($email->validation()->errors() as $error) {
            $codes[] = $error->code();
        }

        self::assertContains($expectedCode, $codes);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function invalidEmailProvider(): array
    {
        return [
            'empty local' => ['@gmail.com', ValidationErrorCode::EMPTY_ADDRESS],
            'empty domain' => ['user@', ValidationErrorCode::EMPTY_DOMAIN],
            'no at' => ['user', ValidationErrorCode::INVALID_FORMAT],
            'double at' => ['user@@gmail.com', ValidationErrorCode::INVALID_FORMAT],
            'whitespace' => ['user @gmail.com', ValidationErrorCode::INVALID_CHARACTER],
            'single label domain' => ['user@gmail', ValidationErrorCode::INVALID_DOMAIN],
        ];
    }

    public function testNormalizationIsLowercaseOnly(): void
    {
        $email = Email::parse('User.Name@GMAIL.COM');

        self::assertSame('user.name@gmail.com', $email->normalized());
        self::assertSame('User.Name@GMAIL.COM', $email->original());
        self::assertSame('user.name@gmail.com', (string) $email);
        self::assertSame('user.name', (string) $email->address());
        self::assertSame('gmail.com', (string) $email->domain());
    }

    public function testNormalizationKeepsPlusTagAndDots(): void
    {
        $email = Email::parse('John.Doe+Tag@Gmail.Com');

        self::assertSame('john.doe+tag@gmail.com', $email->normalized());
    }
}
