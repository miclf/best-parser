<?php

namespace App\Objects;

class Municipality
{
    /**
     * Class constructor.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $nameFr = null,
        public ?string $nameNl = null,
        public ?string $nameDe = null,
        public ?string $nameEn = null,
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
            'name_fr' => $this->nameFr,
            'name_nl' => $this->nameNl,
            'name_de' => $this->nameDe,
            'name_en' => $this->nameEn,
            'nis_code' => $this->id,
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
