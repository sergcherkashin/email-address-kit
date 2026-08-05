<?php

declare(strict_types=1);

namespace EmailAddressKit\Tests\Unit;

use EmailAddressKit\Address\Address;
use EmailAddressKit\Comparison\DefaultComparisonStrategy;
use EmailAddressKit\Domain\Domain;
use EmailAddressKit\Domain\DomainRegistry;
use EmailAddressKit\Email;
use EmailAddressKit\Provider\KnownDomainProvider;
use EmailAddressKit\Support\IdnConverterInterface;
use EmailAddressKit\Typo\EmailCorrector;
use EmailAddressKit\Typo\TypoDetector;
use EmailAddressKit\Validation\EmailValidator;
use EmailAddressKit\Validation\ValidationErrorCode;
use PHPUnit\Framework\TestCase;

/**
 * @covers \EmailAddressKit\Validation\EmailValidator
 * @covers \EmailAddressKit\Address\Address
 */
final class IdnValidatorWithMockTest extends TestCase
{
    public function testEaiLocalWithIdnDomainIsValid(): void
    {
        $idn = $this->createIdnMock([
            'почта.рф' => 'xn--80a1acny.xn--p1ai',
            'компания.онлайн' => 'xn--80aqf2dl.xn--80asehdb',
        ]);

        $validator = new EmailValidator(null, $idn);

        self::assertTrue(
            $validator->validate(
                $this->makeEmail('иван@почта.рф', 'иван', 'почта.рф', $idn, $validator)
            )->isValid()
        );

        self::assertTrue(
            $validator->validate(
                $this->makeEmail('иван.иванов@gmail.com', 'иван.иванов', 'gmail.com', $idn, $validator)
            )->isValid()
        );

        self::assertTrue(
            $validator->validate(
                $this->makeEmail('contact@почта.рф', 'contact', 'почта.рф', $idn, $validator)
            )->isValid()
        );
    }

    public function testValidatorRejectsWhenDomainIdnConversionFails(): void
    {
        $idn = $this->createMock(IdnConverterInterface::class);
        $idn->method('containsNonAscii')->willReturnCallback(
            static function (string $value): bool {
                return \preg_match('/[^\x00-\x7F]/', $value) === 1;
            }
        );
        $idn->method('toAscii')->willReturn(null);
        $idn->method('toLower')->willReturnCallback(
            static function (string $value): string {
                return $value;
            }
        );

        $validator = new EmailValidator(null, $idn);
        $result = $validator->validate(
            $this->makeEmail('иван@почта.рф', 'иван', 'почта.рф', $idn, $validator)
        );

        self::assertFalse($result->isValid());

        $codes = [];
        foreach ($result->errors() as $error) {
            $codes[] = $error->code();
        }

        self::assertContains(ValidationErrorCode::INVALID_DOMAIN, $codes);
    }

    public function testUnicodeLocalPartsCompareByNormalizedForm(): void
    {
        $idn = $this->createIdnMock([]);

        $left = new Address('Иван', $idn);
        $right = new Address('иван', $idn);

        self::assertTrue($left->equals($right));
    }

    public function testUnicodeAndAsciiDomainCanonicalMatch(): void
    {
        $idn = $this->createIdnMock([
            'почта.рф' => 'xn--80a1acny.xn--p1ai',
            'xn--80a1acny.xn--p1ai' => 'xn--80a1acny.xn--p1ai',
        ]);

        $unicode = new Domain('почта.рф', null, $idn);
        $ascii = new Domain('xn--80a1acny.xn--p1ai', null, $idn);

        self::assertSame($unicode->canonical(), $ascii->canonical());
    }

    /**
     * @param array<string, string|null> $map Conversion map for domains.
     */
    private function createIdnMock(array $map): IdnConverterInterface
    {
        $idn = $this->createMock(IdnConverterInterface::class);
        $idn->method('containsNonAscii')->willReturnCallback(
            static function (string $value): bool {
                return \preg_match('/[^\x00-\x7F]/', $value) === 1;
            }
        );
        $idn->method('toLower')->willReturnCallback(
            static function (string $value): string {
                if (\function_exists('mb_strtolower')) {
                    return \mb_strtolower($value, 'UTF-8');
                }

                return \strtolower($value);
            }
        );
        $idn->method('toAscii')->willReturnCallback(
            static function (string $value) use ($map): ?string {
                if (\array_key_exists($value, $map)) {
                    return $map[$value];
                }

                $lower = \function_exists('mb_strtolower')
                    ? \mb_strtolower($value, 'UTF-8')
                    : \strtolower($value);

                if (\array_key_exists($lower, $map)) {
                    return $map[$lower];
                }

                if (\preg_match('/[^\x00-\x7F]/', $value) === 1) {
                    return null;
                }

                return \strtolower($value);
            }
        );

        return $idn;
    }

    
    private function makeEmail(
        string $original,
        string $address,
        string $domain,
        IdnConverterInterface $idn,
        EmailValidator $validator
    ): Email {
        $registry = new DomainRegistry();
        $detector = new TypoDetector(
            new KnownDomainProvider($registry),
            $registry,
            static function (string $value): Email {
                return Email::parse($value);
            }
        );

        return new Email(
            $original,
            new Address($address, $idn),
            new Domain($domain, null, $idn),
            $validator,
            $detector,
            new EmailCorrector($detector),
            new DefaultComparisonStrategy()
        );
    }
}
