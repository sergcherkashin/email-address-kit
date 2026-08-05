<?php

declare(strict_types=1);

namespace EmailAddressKit\Service;

/**
 * Represents an email provider / mail system.
 */
final class EmailService
{
    private string $id;

    private string $name;

    
    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * Returns the stable service identifier.
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Returns the human-readable service name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Limits var_dump / print_r output.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
