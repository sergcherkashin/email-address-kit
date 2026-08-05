<?php

declare(strict_types=1);

namespace EmailAddressKit\Tests\Unit;

use EmailAddressKit\Email;
use EmailAddressKit\EmailFactory;
use EmailAddressKit\Typo\SuggestionReason;
use PHPUnit\Framework\TestCase;

/**
 * @covers \EmailAddressKit\Typo\TypoDetector
 * @covers \EmailAddressKit\Typo\EmailCorrector
 */
final class TypoDetectionTest extends TestCase
{
    protected function setUp(): void
    {
        EmailFactory::setDefault(null);
    }

    /**
     * @dataProvider typoProvider
     */
    public function testDomainTypos(string $input, string $expectedNormalized): void
    {
        $email = Email::parse($input);

        self::assertTrue($email->hasSuggestions());
        self::assertSame($expectedNormalized, $email->correct()->normalized());

        $suggestion = $email->suggestions()[0];
        self::assertSame(SuggestionReason::DOMAIN_TYPO, $suggestion->reason());
        self::assertGreaterThanOrEqual(0.95, $suggestion->score());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function typoProvider(): array
    {
        return [
            'gmail.ru' => ['user@gmail.ru', 'user@gmail.com'],
            'gmial.com' => ['user@gmial.com', 'user@gmail.com'],
            'gmai.com' => ['user@gmai.com', 'user@gmail.com'],
            'yndex.ru' => ['vera@yndex.ru', 'vera@yandex.ru'],
            'jmail.com' => ['imalkin599@jmail.com', 'imalkin599@gmail.com'],
            'inbx.ru' => ['user@inbx.ru', 'user@inbox.ru'],
        ];
    }

    public function testDoesNotCorrectLocalPart(): void
    {
        $email = Email::parse('sarripov@gmail.com');

        self::assertSame('sarripov@gmail.com', $email->correct()->normalized());
    }

    public function testEquivalentSuggestionReason(): void
    {
        $email = Email::parse('user@googlemail.com');
        $suggestions = $email->suggestions();

        self::assertNotEmpty($suggestions);
        self::assertSame(SuggestionReason::DOMAIN_EQUIVALENT, $suggestions[0]->reason());
        self::assertSame('user@gmail.com', $suggestions[0]->email()->normalized());
        // correct() must not apply equivalent rewrite as typo correction
        self::assertSame('user@googlemail.com', $email->correct()->normalized());
    }

    public function testUnknownDomainWithoutCloseMatchHasNoReliableSuggestion(): void
    {
        $email = Email::parse('user@completely-unknown-domain.example');

        self::assertFalse($email->hasSuggestions());
        self::assertSame($email->normalized(), $email->correct()->normalized());
    }
}
