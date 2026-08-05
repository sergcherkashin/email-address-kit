<?php

declare(strict_types=1);

namespace EmailAddressKit;

use EmailAddressKit\Address\Address;
use EmailAddressKit\Comparison\DefaultComparisonStrategy;
use EmailAddressKit\Comparison\EmailComparisonStrategyInterface;
use EmailAddressKit\Disposable\DisposableDomainChecker;
use EmailAddressKit\Disposable\DisposableDomainCheckerInterface;
use EmailAddressKit\Domain\BuiltinCompiledDomainSource;
use EmailAddressKit\Domain\Domain;
use EmailAddressKit\Domain\DomainRegistry;
use EmailAddressKit\Domain\DomainRegistryInterface;
use EmailAddressKit\Exception\InvalidEmailException;
use EmailAddressKit\Provider\KnownDomainProvider;
use EmailAddressKit\Support\IdnConverter;
use EmailAddressKit\Typo\EmailCorrector;
use EmailAddressKit\Typo\EmailCorrectorInterface;
use EmailAddressKit\Typo\TypoDetector;
use EmailAddressKit\Typo\TypoDetectorInterface;
use EmailAddressKit\Validation\EmailValidator;
use EmailAddressKit\Validation\EmailValidatorInterface;

/**
 * Composition root that wires registry, validation, typo detection and comparison.
 */
final class EmailFactory
{
    private static ?EmailFactory $default = null;

    private DomainRegistryInterface $registry;

    private EmailValidatorInterface $validator;

    private TypoDetectorInterface $typoDetector;

    private EmailCorrectorInterface $corrector;

    private EmailComparisonStrategyInterface $comparisonStrategy;

    private DisposableDomainCheckerInterface $disposableChecker;

    
    public function __construct(
        DomainRegistryInterface $registry,
        EmailValidatorInterface $validator,
        TypoDetectorInterface $typoDetector,
        EmailCorrectorInterface $corrector,
        ?EmailComparisonStrategyInterface $comparisonStrategy = null,
        ?DisposableDomainCheckerInterface $disposableChecker = null
    ) {
        $this->registry = $registry;
        $this->validator = $validator;
        $this->typoDetector = $typoDetector;
        $this->corrector = $corrector;
        $this->comparisonStrategy = $comparisonStrategy ?? new DefaultComparisonStrategy();
        $this->disposableChecker = $disposableChecker ?? DisposableDomainChecker::default();
    }

    /**
     * Returns the shared default factory loaded from compiled domain data.
     */
    public static function default(): self
    {
        if (!self::$default instanceof self) {
            self::$default = self::fromRegistry(
                DomainRegistry::fromDataSource(BuiltinCompiledDomainSource::default())
            );
        }

        return self::$default;
    }

    /**
     * Replaces the shared default factory (useful in tests).
     */
    public static function setDefault(?self $factory): void
    {
        self::$default = $factory;
    }

    /**
     * Builds a factory around an existing registry.
     */
    public static function fromRegistry(
        DomainRegistryInterface $registry,
        ?EmailComparisonStrategyInterface $comparisonStrategy = null,
        ?EmailValidatorInterface $validator = null,
        ?DisposableDomainCheckerInterface $disposableChecker = null
    ): self {
        $validator ??= new EmailValidator();
        $knownDomains = new KnownDomainProvider($registry);

        $factory = null;
        $typoDetector = new TypoDetector(
            $knownDomains,
            $registry,
            static function (string $value) use (&$factory): Email {
                /** @var self $factory */
                return $factory->parse($value);
            }
        );
        $corrector = new EmailCorrector($typoDetector);

        $factory = new self(
            $registry,
            $validator,
            $typoDetector,
            $corrector,
            $comparisonStrategy,
            $disposableChecker
        );

        return $factory;
    }

    /**
     * Returns the disposable-domain checker used by this factory.
     */
    public function disposableChecker(): DisposableDomainCheckerInterface
    {
        return $this->disposableChecker;
    }

    /**
     * Returns the domain registry used by this factory.
     */
    public function registry(): DomainRegistryInterface
    {
        return $this->registry;
    }

    /**
     * Parses a raw email string into an Email object.
     *
     * Invalid emails do not throw; use Email::isValid() afterwards.
     *
     *
     * @throws InvalidEmailException When the value is not a usable string representation.
     */
    public function parse(string $input): Email
    {
        $original = $input;
        $atPos = \strrpos($input, '@');

        if ($atPos === false) {
            $addressValue = $input;
            $domainValue = '';
        } else {
            $addressValue = \substr($input, 0, $atPos);
            $domainValue = \substr($input, $atPos + 1);
        }

        $idn = new IdnConverter();
        $domainAscii = $idn->toAscii($domainValue);
        $domainInfo = $this->registry->find($domainAscii ?? $idn->toLower($domainValue));

        return new Email(
            $original,
            new Address($addressValue, $idn),
            new Domain($domainValue, $domainInfo, $idn),
            $this->validator,
            $this->typoDetector,
            $this->corrector,
            $this->comparisonStrategy,
            $this->disposableChecker
        );
    }
}
