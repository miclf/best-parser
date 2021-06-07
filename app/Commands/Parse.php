<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use App\Parser\Municipality as MunicipalityParser;

class Parse extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'parse {type}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Parse an XML file';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        [$region, $dataType] = $this->parseDocumentType($this->argument('type'));

        $parser = match ($dataType) {
            'Municipality' => new MunicipalityParser(),
        };

        $data = $parser->parse($region);
    }

    /**
     * Separate the region name and the type of data.
     */
    protected function parseDocumentType(string $type): array
    {
        // Separate the region name and the type of data.
        // Regexp slightly modified from the one found there:
        // https://stackoverflow.com/a/4519809
        preg_match_all('/(?:^|[A-Z])[a-z]+/', $type, $matches);

        return $matches[0];
    }
}
