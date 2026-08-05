<?php

declare(strict_types=1);

namespace EmailAddressKit\Disposable;

/**
 * Merges multiple disposable-domain sources into one stream.
 *
 * Later sources can add domains; duplicates are fine and removed at compile time.
 */
final class CompositeDisposableDomainSource implements DisposableDomainSourceInterface
{
    private string $id;

    /** @var DisposableDomainSourceInterface[] */
    private array $sources;

    /**
     * @param DisposableDomainSourceInterface[] $sources Sources in fetch order.
     */
    public function __construct(array $sources, string $id = 'composite')
    {
        $this->sources = $sources;
        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(): iterable
    {
        foreach ($this->sources as $source) {
            foreach ($source->fetch() as $domain) {
                yield $domain;
            }
        }
    }
}
