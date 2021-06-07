<?php

namespace App\Parser;

use XMLParser;
use Illuminate\Support\Facades\DB;
use App\Objects\Municipality as MunicipalityObject;

class Municipality
{
    protected float $startTime;

    protected ?string $previousTagName = null;
    protected ?string $currentTagName = null;

    protected ?string $currentDataLanguage = null;

    protected ?MunicipalityObject $currentObject = null;

    // Flags.
    protected bool $isInMunicipality = false;
    protected bool $isInMunicipalityCode = false;
    protected bool $isInMunicipalityName = false;

    /**
     * Parse an XML file of municipalities.
     */
    public function parse(string $region): void
    {
        // $this->startTime = microtime(true);

        $xmlFile = base_path("data/{$region}Municipality.xml");

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

        // Display performance stats.
        // $elapsed = round(microtime(true) - $this->startTime, 3);
        // $memoryUsage = $this->getHumanReadableSize(memory_get_usage());
        // $memoryPeakUsage = $this->getHumanReadableSize(memory_get_peak_usage());

        // echo "Elapsed time: {$elapsed} sec".PHP_EOL;
        // echo "Memory: {$memoryUsage} (peak {$memoryPeakUsage})".PHP_EOL;
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
            'tns:Municipality' => $this->isInMunicipality = true,
            'com:municipalityCode' => $this->isInMunicipalityCode = true,
            'com:municipalityName' => $this->isInMunicipalityName = true,
            default => null,
        };

        // We’ve just entered a new Municipality element.
        // We create a new object to host its data.
        if ($name === 'tns:Municipality') {
            $this->currentObject = new MunicipalityObject();
        }
    }

    /**
     * Handler called when the parser leaves an element.
     */
    protected function endElement(XMLParser $parser, string $name)
    {
        // Potentially set a flag to false.
        match ($this->currentTagName) {
            'tns:Municipality' => $this->isInMunicipality = false,
            'com:municipalityCode' => $this->isInMunicipalityCode = false,
            'com:municipalityName' => $this->isInMunicipalityName = false,
            default => null,
        };

        // We just finished parsing a municipality. Add it to the database.
        if ($name === 'tns:Municipality') {
            DB::table('municipalities')->insert($this->currentObject->toArray());
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
        if ($this->isInMunicipalityName && $this->currentTagName === 'com:language') {
            $this->currentDataLanguage = $data;
        }


        // Grab data.

        // ID.
        if ($this->isInMunicipalityCode && $this->currentTagName === 'com:objectIdentifier') {
            return $this->currentObject->id = $data;
        }

        // Name.
        if ($this->isInMunicipalityName && $this->currentTagName === 'com:spelling') {
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

        // Namespace (indicates from which region the data comes from).
        if ($this->isInMunicipalityCode && $this->currentTagName === 'com:namespace') {
            return $this->currentObject->namespace = $data;
        }

        // Object version (in the region’s database).
        if ($this->isInMunicipalityCode && $this->currentTagName === 'com:versionIdentifier') {
            return $this->currentObject->version = $data;
        }
    }

    /**
     * Helper to format file sizes.
     */
    protected function getHumanReadableSize(int $sizeInBytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        if ($sizeInBytes == 0) {
            return '0 '.$units[1];
        }

        for ($i = 0; $sizeInBytes > 1024; $i++) {
            $sizeInBytes /= 1024;
        }

        return round($sizeInBytes, 2).' '.$units[$i];
    }
}
