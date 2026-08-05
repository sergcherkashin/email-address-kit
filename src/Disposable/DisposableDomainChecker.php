<?php

declare(strict_types=1);

namespace EmailAddressKit\Disposable;

use EmailAddressKit\Email;
use EmailAddressKit\Support\IdnConverter;
use EmailAddressKit\Support\IdnConverterInterface;

/**
 * O(1) disposable-domain checker backed by a compiled PHP hash map.
 */
final class DisposableDomainChecker implements DisposableDomainCheckerInterface
{
    private string $compiledFile;

    private IdnConverterInterface $idn;

    private bool $checkParentDomains;

    /** @var array<string, true>|null */
    private ?array $map = null;

    
    public function __construct(
        string $compiledFile,
        ?IdnConverterInterface $idn = null,
        bool $checkParentDomains = true
    ) {
        $this->compiledFile = $compiledFile;
        $this->idn = $idn ?? new IdnConverter();
        $this->checkParentDomains = $checkParentDomains;
    }

    /**
     * Creates a checker for the package compiled snapshot.
     */
    public static function default(bool $checkParentDomains = true): self
    {
        $path = \dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'resources'
            . DIRECTORY_SEPARATOR . 'compiled'
            . DIRECTORY_SEPARATOR . 'disposable.php';

        return new self($path, null, $checkParentDomains);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $email
     */
    public function isDisposable($email): bool
    {
        if ($email instanceof Email) {
            return $this->isDisposableDomain($email->domain()->ascii());
        }

        if (!\is_string($email)) {
            return false;
        }

        $atPos = \strrpos($email, '@');

        if ($atPos === false) {
            return $this->isDisposableDomain($email);
        }

        return $this->isDisposableDomain(\substr($email, $atPos + 1));
    }

    /**
     * {@inheritdoc}
     */
    public function isDisposableDomain(string $domain): bool
    {
        $ascii = $this->idn->toAscii($domain);

        if ($ascii === null || $ascii === '') {
            $ascii = \strtolower(\trim($domain));
        }

        $ascii = \rtrim($ascii, '.');

        if ($ascii === '') {
            return false;
        }

        $map = $this->map();

        if (isset($map[$ascii])) {
            return true;
        }

        if (!$this->checkParentDomains) {
            return false;
        }

        $parts = \explode('.', $ascii);
        $count = \count($parts);

        for ($i = 1; $i < $count - 1; $i++) {
            $parent = \implode('.', \array_slice($parts, $i));

            if (isset($map[$parent])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lazily loads the compiled map.
     *
     * @return array<string, true>
     */
    private function map(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        if (!\is_file($this->compiledFile)) {
            $this->map = [];

            return $this->map;
        }

        /** @var mixed $loaded */
        $loaded = require $this->compiledFile;

        if (!\is_array($loaded)) {
            $this->map = [];

            return $this->map;
        }

        /** @var array<string, true> $loaded */
        $this->map = $loaded;

        return $this->map;
    }
}
