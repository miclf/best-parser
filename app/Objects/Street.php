<?php

namespace App\Objects;

class Street
{
    /**
     * Class constructor.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $municipalityId = null,
        public ?string $municipalityVersion = null,
        public ?string $validFrom = null,
        public ?string $nameFr = null,
        public ?string $nameNl = null,
        public ?string $nameDe = null,
        public ?string $status = null,
        public ?string $statusValidFrom = null,
        public ?string $type = null,
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
            'municipality_id' => $this->municipalityId,
            'municipality_version' => $this->municipalityVersion,
            'valid_from' => $this->validFrom,
            'name_fr' => $this->nameFr,
            'name_nl' => $this->nameNl,
            'name_de' => $this->nameDe,
            'status' => $this->status,
            'status_valid_from' => $this->statusValidFrom,
            'type' => $this->type,
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
