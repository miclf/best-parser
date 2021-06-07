<?php

namespace App\Objects;

class Postcode
{
    /**
     * Class constructor.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $namespace = null,
        public ?string $version = null,
    ) {
        // Nothin’.
    }

    /**
     * Returns an array representation of the object.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'namespace' => $this->namespace,
            'version' => $this->version,
        ];
    }

    /**
     * List the types of info that are stored by this object.
     */
    public static function columnNames(): array
    {
        return array_keys((new static)->toArray());
    }
}
