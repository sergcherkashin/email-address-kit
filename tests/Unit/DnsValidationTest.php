<?php

declare(strict_types=1);

namespace EmailAddressKit\Tests\Unit;

use EmailAddressKit\Domain\BuiltinCompiledDomainSource;
use EmailAddressKit\Domain\DomainRegistry;
use EmailAddressKit\Email;
use EmailAddressKit\EmailFactory;
use EmailAddressKit\Validation\DnsLookupInterface;
use EmailAddressKit\Validation\EmailValidator;
use EmailAddressKit\Validation\ValidationErrorCode;
use EmailAddressKit\Validation\ValidationOptions;
use PHPUnit\Framework\TestCase;

/**
 * @covers \EmailAddressKit\Validation\EmailValidator
 * @covers \EmailAddressKit\Validation\ValidationOptions
 */
final class DnsValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        EmailFactory::setDefault(null);
    }

    public function testDnsCheckIsDisabledByDefault(): void
    {
        $dns = $this->createMock(DnsLookupInterface::class);
        $dns->expects(self::never())->method('hasMxOrARecord');

        $this->bootFactory($dns);

        self::assertTrue(Email::parse('user@example.com')->isValid());
    }

    public function testDnsCheckPassesWhenMxOrAExists(): void
    {
        $dns = $this->createMock(DnsLookupInterface::class);
        $dns->expects(self::once())
            ->method('hasMxOrARecord')
            ->with('gmail.com')
            ->willReturn(true);

        $this->bootFactory($dns);

        self::assertTrue(Email::parse('user@gmail.com')->isValid(ValidationOptions::checkDns()));
    }

    public function testDnsCheckFailsWhenNoRecords(): void
    {
        $dns = $this->createMock(DnsLookupInterface::class);
        $dns->expects(self::once())
            ->method('hasMxOrARecord')
            ->with('example.com')
            ->willReturn(false);

        $this->bootFactory($dns);

        $result = Email::parse('user@example.com')->validation(ValidationOptions::checkDns());

        self::assertFalse($result->isValid());
        self::assertSame(ValidationErrorCode::DNS_CHECK_FAILED, $result->errors()[0]->code());
    }

    public function testDnsCheckSkippedWhenSyntaxInvalid(): void
    {
        $dns = $this->createMock(DnsLookupInterface::class);
        $dns->expects(self::never())->method('hasMxOrARecord');

        $this->bootFactory($dns);

        $result = Email::parse('user@')->validation(ValidationOptions::checkDns());

        self::assertFalse($result->isValid());

        $codes = [];
        foreach ($result->errors() as $error) {
            $codes[] = $error->code();
        }

        self::assertNotContains(ValidationErrorCode::DNS_CHECK_FAILED, $codes);
    }

    public function testValidatorDirectlyWithDnsOption(): void
    {
        $dns = $this->createMock(DnsLookupInterface::class);
        $dns->method('hasMxOrARecord')->willReturn(false);

        $validator = new EmailValidator($dns);
        $email = Email::parse('user@example.com');

        self::assertTrue($validator->validate($email)->isValid());
        self::assertFalse($validator->validate($email, ValidationOptions::checkDns())->isValid());
    }

    
    private function bootFactory(DnsLookupInterface $dnsLookup): void
    {
        $registry = DomainRegistry::fromDataSource(BuiltinCompiledDomainSource::default());

        EmailFactory::setDefault(
            EmailFactory::fromRegistry(
                $registry,
                null,
                new EmailValidator($dnsLookup)
            )
        );
    }
}
