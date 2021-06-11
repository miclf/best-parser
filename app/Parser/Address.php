<?php

namespace App\Parser;

use XMLParser;
use Carbon\Carbon;
use proj4php\Proj;
use proj4php\Point;
use proj4php\Proj4php;
use Illuminate\Support\Facades\DB;
use App\Objects\Address as AddressObject;

class Address
{
    protected int $count = 0;
    protected array $stuffToSave = [];

    protected ?string $previousTagName = null;
    protected ?string $currentTagName = null;

    protected ?string $currentDataLanguage = null;

    protected ?AddressObject $currentObject = null;

    // Stuff to convert coordinates.
    protected Proj4php $proj4;
    protected Proj $lambertProjection;
    protected Proj $wgsProjection;

    // Flags.
    protected bool $isInAddress = false;
    protected bool $isInAddressCode = false;
    protected bool $isInAddressPosition = false;
    protected bool $isInAddressStatus = false;
    protected bool $isInHasStreetName = false;
    protected bool $isInHasMunicipality = false;
    protected bool $isInHasPostalInfo = false;

    /**
     * Parse an XML file of municipalities.
     */
    public function parse(string $region): void
    {
        // Initialize stuff to convert coordinates.

        $this->proj4 = new Proj4php;

        // Belgian Lambert 72
        $this->lambertProjection = new Proj('EPSG:31370', $this->proj4);
        // WGS 84
        $this->wgsProjection = new Proj('EPSG:4326', $this->proj4);


        $xmlFile = base_path("data/{$region}Address.xml");

        // Create and configure the parser.
        $parser = xml_parser_create();

        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);

        xml_set_element_handler($parser, [$this, 'startElement'], [$this, 'endElement']);
        xml_set_character_data_handler($parser, [$this, 'handleCharacterData']);

        $fp = fopen($xmlFile, 'r');

        // Parse the XML file.
        while ($data = fread($fp, 4096)) {
            if (!xml_parse($parser, $data, feof($fp))) {
                exit(sprintf(
                    "XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($parser)),
                    xml_get_current_line_number($parser))
                );
            }
        }

        xml_parser_free($parser);

        // Insert remaining stuff.
        DB::table('addresses')->insert($this->stuffToSave);
    }

    /**
     * Handler called when the parser enters into an element.
     */
    protected function startElement(XMLParser $parser, string $name, array $attrs): void
    {
        $this->previousTagName = $this->currentTagName;
        $this->currentTagName = $name;

        // Potentially set a flag to true.
        match ($this->currentTagName) {
            'tns:Address' => $this->isInAddress = true,
            'add:addressCode', 'com:addressCode' => $this->isInAddressCode = true,
            'add:addressPosition', 'com:addressPosition' => $this->isInAddressPosition = true,
            'add:addressStatus', 'com:addressStatus' => $this->isInAddressStatus = true,
            'add:hasStreetname', 'com:hasStreetname' => $this->isInHasStreetName = true,
            'add:hasMunicipality', 'com:hasMunicipality' => $this->isInHasMunicipality = true,
            'add:hasPostalInfo', 'com:hasPostalInfo' => $this->isInHasPostalInfo = true,
            default => null,
        };

        // We’ve just entered a new Address element.
        if ($name === 'tns:Address') {
            // We create a new object to host its data.
            $this->currentObject = new AddressObject();

            // We grab the value of the `beginLifeSpanVersion` attribute.
            $this->currentObject->validFrom = new Carbon($attrs['beginLifeSpanVersion']);
        }
    }

    /**
     * Handler called when the parser leaves an element.
     */
    protected function endElement(XMLParser $parser, string $name)
    {
        // Potentially set a flag to false.
        match ($name) {
            'tns:Address' => $this->isInAddress = false,
            'add:addressCode', 'com:addressCode' => $this->isInAddressCode = false,
            'add:addressPosition', 'com:addressPosition' => $this->isInAddressPosition = false,
            'add:addressStatus', 'com:addressStatus' => $this->isInAddressStatus = false,
            'add:hasStreetname', 'com:hasStreetname' => $this->isInHasStreetName = false,
            'add:hasMunicipality', 'com:hasMunicipality' => $this->isInHasMunicipality = false,
            'add:hasPostalInfo', 'com:hasPostalInfo' => $this->isInHasPostalInfo = false,
            default => null,
        };

        // We just finished parsing an address. Add it to the database.
        if ($name === 'tns:Address') {
            // Increment counter.
            $this->count++;

            $dataToInsert = $this->currentObject->toArray();
            // Add a raw query to add the MySQL Point.
            $dataToInsert['location'] = DB::raw(
                "(ST_PointFromText('POINT({$dataToInsert['longitude']} {$dataToInsert['latitude']})'))"
            );

            $this->stuffToSave[] = $dataToInsert;

            // Insert everything.
            if ($this->count === 2500) {
                DB::table('addresses')->insert($this->stuffToSave);

                // Reset things.
                $this->count = 0;
                $this->stuffToSave = [];
            }
        }
    }

    /**
     * Handler called when the parser finds CDATA.
     */
    protected function handleCharacterData(XMLParser $parser, string $data)
    {
        $data = trim($data);

        // Return early if there is no data.
        if ($data === '') {
            return;
        }


        // Grab data.

        // ID.
        if ($this->isInAddressCode && str_ends_with($this->currentTagName, ':objectIdentifier')) {
            $this->currentObject->id = $data;
        }

        // Namespace (indicates from which region the data comes from).
        if ($this->isInAddressCode && str_ends_with($this->currentTagName, ':namespace')) {
            return $this->currentObject->namespace = $data;
        }

        // Object version (in the region’s database).
        if ($this->isInAddressCode && str_ends_with($this->currentTagName, ':versionIdentifier')) {
            return $this->currentObject->version = $data;
        }

        // Coordinates.
        if ($this->isInAddressPosition && $this->currentTagName === 'gml:pos') {
            $lambertCoords = explode(' ', $data);

            // Store coordinates in Lambert 72.
            $this->currentObject->epsg31370X = $lambertCoords[0];
            $this->currentObject->epsg31370Y = $lambertCoords[1];

            // Convert coordinates to WGS 84.
            $pointSrc = new Point($lambertCoords[0], $lambertCoords[1], $this->lambertProjection);
            $converted = $this->proj4->transform($this->wgsProjection, $pointSrc);

            // x = latitude, y = longitude.
            // No, this does not make any sense.
            // But the Internet has answers about that mess:
            // https://dba.stackexchange.com/a/229689
            $this->currentObject->latitude = $converted->x;
            $this->currentObject->longitude = $converted->y;
        }

        // Position geometry method (whatever that means).
        if ($this->isInAddressPosition && str_ends_with($this->currentTagName, ':positionGeometryMethod')) {
            return $this->currentObject->positionGeometryMethod = $data;
        }

        // Position specification (whatever that means).
        if ($this->isInAddressPosition && str_ends_with($this->currentTagName, ':positionSpecification')) {
            return $this->currentObject->positionSpecification = $data;
        }

        // House number.
        if (str_ends_with($this->currentTagName, ':houseNumber')) {
            return $this->currentObject->houseNumber = $data;
        }

        // Box number.
        if (str_ends_with($this->currentTagName, ':boxNumber')) {
            return $this->currentObject->boxNumber = $data;
        }

        // Sort field.
        if (str_ends_with($this->currentTagName, ':addressSortfield')) {
            return $this->currentObject->sortField = $data;
        }

        // Officially assigned (whatever that means).
        if (str_ends_with($this->currentTagName, ':officiallyAssigned')) {
            return $this->currentObject->officiallyAssigned = ($data === 'true' ? 1 : 0);
        }

        // Status.
        if ($this->isInAddressStatus && str_ends_with($this->currentTagName, ':status')) {
            return $this->currentObject->status = $data;
        }

        // Status valid from.
        if ($this->isInAddressStatus && str_ends_with($this->currentTagName, ':validFrom')) {
            return $this->currentObject->statusValidFrom = new Carbon($data);
        }

        // Street.

        // ID of the street the address belongs to.
        if ($this->isInHasStreetName && str_ends_with($this->currentTagName, ':objectIdentifier')) {
            return $this->currentObject->streetId = $data;
        }

        // Namespace of street.
        if ($this->isInHasStreetName && str_ends_with($this->currentTagName, ':namespace')) {
            return $this->currentObject->streetNamespace = $data;
        }

        // Object version of street.
        if ($this->isInHasStreetName && str_ends_with($this->currentTagName, ':versionIdentifier')) {
            return $this->currentObject->streetVersion = $data;
        }

        // Municipality.

        // ID of the municipality the address belongs to.
        if ($this->isInHasMunicipality && str_ends_with($this->currentTagName, ':objectIdentifier')) {
            return $this->currentObject->municipalityId = $data;
        }

        // Namespace of municipality.
        if ($this->isInHasMunicipality && str_ends_with($this->currentTagName, ':namespace')) {
            return $this->currentObject->municipalityNamespace = $data;
        }

        // Object version of municipality.
        if ($this->isInHasMunicipality && str_ends_with($this->currentTagName, ':versionIdentifier')) {
            return $this->currentObject->municipalityVersion = $data;
        }

        // Postcode.

        // ID of the postcode the address is linked to.
        if ($this->isInHasPostalInfo && str_ends_with($this->currentTagName, ':objectIdentifier')) {
            $this->currentObject->postcode = $data;
            return $this->currentObject->postcodeId = $data;
        }

        // Namespace of postcode.
        if ($this->isInHasPostalInfo && str_ends_with($this->currentTagName, ':namespace')) {
            return $this->currentObject->postcodeNamespace = $data;
        }

        // Object version of postcode.
        if ($this->isInHasPostalInfo && str_ends_with($this->currentTagName, ':versionIdentifier')) {
            return $this->currentObject->postcodeVersion = $data;
        }
    }
}
