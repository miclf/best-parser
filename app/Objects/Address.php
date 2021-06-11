<?php

namespace App\Objects;

class Address
{
    /**
     * Class constructor.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $namespace = null,
        public ?string $version = null,
        public ?string $longitude = null,
        public ?string $latitude = null,
        public $location = null,
        public ?string $epsg31370X = null,
        public ?string $epsg31370Y = null,
        public ?string $positionGeometryMethod = null,
        public ?string $positionSpecification = null,
        public ?string $houseNumber = null,
        public ?string $boxNumber = null,
        public ?string $sortField = null,
        public ?string $postcode = null,
        public ?string $status = null,
        public ?string $statusValidFrom = null,
        public ?string $officiallyAssigned = null,
        public ?string $streetId = null,
        public ?string $streetNamespace = null,
        public ?string $streetVersion = null,
        public ?string $municipalityId = null,
        public ?string $municipalityNamespace = null,
        public ?string $municipalityVersion = null,
        public ?string $postcodeId = null,
        public ?string $postcodeNamespace = null,
        public ?string $postcodeVersion = null,
        public ?string $validFrom = null,
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
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'location' => $this->location,
            'epsg_31370_x' => $this->epsg31370X,
            'epsg_31370_y' => $this->epsg31370Y,
            'position_geometry_method' => $this->positionGeometryMethod,
            'position_specification' => $this->positionSpecification,
            'house_number' => $this->houseNumber,
            'box_number' => $this->boxNumber,
            'sort_field' => $this->sortField,
            'postcode' => $this->postcode,
            'status' => $this->status,
            'status_valid_from' => $this->statusValidFrom,
            'officially_assigned' => $this->officiallyAssigned,
            'street_id' => $this->streetId,
            'street_namespace' => $this->streetNamespace,
            'street_version' => $this->streetVersion,
            'municipality_id' => $this->municipalityId,
            'municipality_namespace' => $this->municipalityNamespace,
            'municipality_version' => $this->municipalityVersion,
            'postcode_id' => $this->postcodeId,
            'postcode_namespace' => $this->postcodeNamespace,
            'postcode_version' => $this->postcodeVersion,
            'valid_from' => $this->validFrom,
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
