<?php

namespace App\Parser;

use XMLParser;
use Illuminate\Support\Facades\DB;
use App\Objects\Street as StreetObject;
use Carbon\Carbon;

class Street
{
    protected float $startTime;

    protected ?string $previousTagName = null;
    protected ?string $currentTagName = null;

    protected ?string $currentDataLanguage = null;

    protected ?StreetObject $currentObject = null;

    // Flags.
    protected bool $isInStreet = false;
    protected bool $isInStreetCode = false;
    protected bool $isInStreetName = false;
    protected bool $isInStreetNameStatus = false;
    protected bool $isInAssignedBy = false;

    /**
     * Parse an XML file of municipalities.
     */
    public function parse(string $region): void
    {
        $xmlFile = base_path("data/{$region}Streetname.xml");

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
            'tns:Streetname' => $this->isInStreet = true,
            'com:streetnameCode' => $this->isInStreetCode = true,
            'com:streetname' => $this->isInStreetName = true,
            'com:streetnameStatus' => $this->isInStreetNameStatus = true,
            'com:isAssignedBy' => $this->isInAssignedBy = true,
            default => null,
        };

        // We’ve just entered a new Street element.
        if ($name === 'tns:Streetname') {
            // We create a new object to host its data.
            $this->currentObject = new StreetObject();

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
            'tns:Streetname' => $this->isInStreet = false,
            'com:streetnameCode' => $this->isInStreetCode = false,
            'com:streetname' => $this->isInStreetName = false,
            'com:streetnameStatus' => $this->isInStreetNameStatus = false,
            'com:isAssignedBy' => $this->isInAssignedBy = false,
            default => null,
        };

        // We just finished parsing a street. Add it to the database.
        if ($name === 'tns:Streetname') {
            DB::table('streets')->insert($this->currentObject->toArray());
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

        // If we are in the tag of a municipality name, store the language of that name.
        if ($this->isInStreetName && $this->currentTagName === 'com:language') {
            $this->currentDataLanguage = $data;
        }


        // Grab data.

        // ID.
        if ($this->isInStreetCode && $this->currentTagName === 'com:objectIdentifier') {
            $this->currentObject->id = $data;
        }

        // Name.
        if ($this->isInStreetName && $this->currentTagName === 'com:spelling') {
            $property = 'name'.ucfirst($this->currentDataLanguage);

            // Due to an undocumented ‘splitting’ functionality, the parser may
            // split the CDATA of an element into multiple pieces and then
            // call this CDATA handler multiple times. As a result, we
            // need to check if we already have some data for the
            // piece of data here and, if that’s the case, we
            // need to concatenate it with the new data.
            if (isset($this->currentObject->{$property})) {
                $this->currentObject->{$property} .= $data;
            } else {
                $this->currentObject->{$property} = $data;
            }

            return;
        }

        // Status.
        if ($this->isInStreetNameStatus && $this->currentTagName === 'com:status') {
            return $this->currentObject->status = $data;
        }

        // Status valid from.
        if ($this->isInStreetNameStatus && $this->currentTagName === 'com:validFrom') {
            return $this->currentObject->statusValidFrom = new Carbon($data);
        }

        // Type of street.
        if ($this->currentTagName === 'com:streetnameType') {
            return $this->currentObject->type = $data;
        }

        // ID of the municipality the street belongs to.
        if ($this->isInAssignedBy && $this->currentTagName === 'com:objectIdentifier') {
            return $this->currentObject->municipalityId = $data;
        }

        // Object version of municipality.
        if ($this->isInAssignedBy && $this->currentTagName === 'com:versionIdentifier') {
            return $this->currentObject->municipalityVersion = $data;
        }

        // Namespace (indicates from which region the data comes from).
        if ($this->isInStreetCode && $this->currentTagName === 'com:namespace') {
            return $this->currentObject->namespace = $data;
        }

        // Object version (in the region’s database).
        if ($this->isInStreetCode && $this->currentTagName === 'com:versionIdentifier') {
            return $this->currentObject->version = $data;
        }
    }
}
