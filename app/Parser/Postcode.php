<?php

namespace App\Parser;

use XMLParser;
use Illuminate\Support\Facades\DB;
use App\Objects\Postcode as PostcodeObject;

class Postcode
{
    protected ?string $previousTagName = null;
    protected ?string $currentTagName = null;

    protected ?PostcodeObject $currentObject = null;

    // Flags.
    protected bool $isInPostalInfo = false;

    /**
     * Parse an XML file of postcodes.
     */
    public function parse(string $region): void
    {
        $xmlFile = base_path("data/{$region}Postalinfo.xml");

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
            'tns:PostalInfo' => $this->isInPostalInfo = true,
            default => null,
        };

        // We’ve just entered a new PostalInfo element.
        // We create a new object to host its data.
        if ($name === 'tns:PostalInfo') {
            $this->currentObject = new PostcodeObject();
        }
    }

    /**
     * Handler called when the parser leaves an element.
     */
    protected function endElement(XMLParser $parser, string $name)
    {
        // Potentially set a flag to false.
        match ($name) {
            'tns:PostalInfo' => $this->isInPostalInfo = false,
            default => null,
        };

        // We just finished parsing a postcode. Add it to the database.
        if ($name === 'tns:PostalInfo') {
            DB::table('postcodes')->insert($this->currentObject->toArray());
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
        if ($this->isInPostalInfo && $this->currentTagName === 'com:objectIdentifier') {
            return $this->currentObject->id = $data;
        }

        // Namespace (indicates from which region the data comes from).
        if ($this->isInPostalInfo && $this->currentTagName === 'com:namespace') {
            return $this->currentObject->namespace = $data;
        }

        // Object version (in the region’s database).
        if ($this->isInPostalInfo && $this->currentTagName === 'com:versionIdentifier') {
            return $this->currentObject->version = $data;
        }
    }
}
