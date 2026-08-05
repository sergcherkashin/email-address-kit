<?php

declare(strict_types=1);

namespace EmailAddressKit\Tests\Unit;

use EmailAddressKit\Disposable\DisposableDomainChecker;
use EmailAddressKit\Disposable\DisposableDomainCompiler;
use EmailAddressKit\Disposable\DisposableDomainListParser;
use EmailAddressKit\Disposable\LocalFileDisposableDomainSource;
use EmailAddressKit\Email;
use EmailAddressKit\EmailFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \EmailAddressKit\Disposable\DisposableDomainChecker
 * @covers \EmailAddressKit\Disposable\DisposableDomainCompiler
 * @covers \EmailAddressKit\Disposable\DisposableDomainListParser
 * @covers \EmailAddressKit\Disposable\LocalFileDisposableDomainSource
 * @covers \EmailAddressKit\Email::isDisposable
 */
final class DisposableDomainTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        EmailFactory::setDefault(null);
        $this->tempDir = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'email-address-kit-disposable-' . \uniqid('', true);
        \mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        EmailFactory::setDefault(null);

        foreach (\glob($this->tempDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            @\unlink($file);
        }
        @\rmdir($this->tempDir);
    }

    public function testParserIgnoresCommentsAndBlankLines(): void
    {
        $domains = DisposableDomainListParser::parse(
            "# comment\nmailinator.com\n\n; also comment\nyopmail.com\n",
            'txt'
        );

        self::assertSame(['mailinator.com', 'yopmail.com'], $domains);
    }

    public function testParserJsonSupportsStringsAndObjects(): void
    {
        $domains = DisposableDomainListParser::parse(
            '["mailinator.com", {"domain": "yopmail.com"}]',
            'json'
        );

        self::assertSame(['mailinator.com', 'yopmail.com'], $domains);
    }

    public function testCompilerAppliesAllowlistAndWritesMap(): void
    {
        $listFile = $this->tempDir . DIRECTORY_SEPARATOR . 'list.txt';
        \file_put_contents($listFile, "mailinator.com\ngmail.com\nyopmail.com\n");

        $compiler = new DisposableDomainCompiler($this->tempDir);
        $result = $compiler->compile(
            new LocalFileDisposableDomainSource($listFile, 'txt', 'test-list'),
            null,
            ['gmail.com']
        );

        self::assertSame(2, $result['count']);
        self::assertSame('test-list', $result['source']);

        /** @var array<string, true> $map */
        $map = require $this->tempDir . DIRECTORY_SEPARATOR . 'disposable.php';

        self::assertTrue($map['mailinator.com']);
        self::assertTrue($map['yopmail.com']);
        self::assertArrayNotHasKey('gmail.com', $map);
    }

    public function testCheckerMatchesExactAndParentDomains(): void
    {
        $listFile = $this->tempDir . DIRECTORY_SEPARATOR . 'list.txt';
        \file_put_contents($listFile, "mailinator.com\n");

        $compiler = new DisposableDomainCompiler($this->tempDir);
        $compiler->compile(new LocalFileDisposableDomainSource($listFile, 'txt', 'test'));

        $checker = new DisposableDomainChecker(
            $this->tempDir . DIRECTORY_SEPARATOR . 'disposable.php'
        );

        self::assertTrue($checker->isDisposableDomain('mailinator.com'));
        self::assertTrue($checker->isDisposableDomain('foo.mailinator.com'));
        self::assertTrue($checker->isDisposable('user@mailinator.com'));
        self::assertFalse($checker->isDisposableDomain('gmail.com'));
        self::assertFalse($checker->isDisposable('user@gmail.com'));
    }

    public function testCheckerCanDisableParentMatching(): void
    {
        $listFile = $this->tempDir . DIRECTORY_SEPARATOR . 'list.txt';
        \file_put_contents($listFile, "mailinator.com\n");

        (new DisposableDomainCompiler($this->tempDir))
            ->compile(new LocalFileDisposableDomainSource($listFile, 'txt', 'test'));

        $checker = new DisposableDomainChecker(
            $this->tempDir . DIRECTORY_SEPARATOR . 'disposable.php',
            null,
            false
        );

        self::assertTrue($checker->isDisposableDomain('mailinator.com'));
        self::assertFalse($checker->isDisposableDomain('foo.mailinator.com'));
    }

    public function testEmailIsDisposableUsesBundledSnapshot(): void
    {
        self::assertTrue(Email::parse('a@yopmail.com')->isDisposable());
        self::assertFalse(Email::parse('a@gmail.com')->isDisposable());
        self::assertFalse(Email::parse('a@mail.ru')->isDisposable());
    }

    public function testFactoryAcceptsCustomDisposableChecker(): void
    {
        $listFile = $this->tempDir . DIRECTORY_SEPARATOR . 'list.txt';
        \file_put_contents($listFile, "example-disposable.test\n");
        (new DisposableDomainCompiler($this->tempDir))
            ->compile(new LocalFileDisposableDomainSource($listFile, 'txt', 'custom'));

        $checker = new DisposableDomainChecker(
            $this->tempDir . DIRECTORY_SEPARATOR . 'disposable.php'
        );

        $factory = EmailFactory::fromRegistry(
            EmailFactory::default()->registry(),
            null,
            null,
            $checker
        );

        self::assertTrue($factory->parse('user@example-disposable.test')->isDisposable());
        self::assertFalse($factory->parse('user@mailinator.com')->isDisposable());
    }
}
