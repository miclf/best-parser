<?php

namespace App\Commands;

use App\Unzipper;
use App\Parser\Street as StreetParser;
use App\Parser\Address as AddressParser;
use App\Parser\Postcode as PostcodeParser;
use LaravelZero\Framework\Commands\Command;
use App\Parser\Municipality as MunicipalityParser;

class RunEverything extends Command
{
    protected float $startTime;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'run';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Run the whole thing';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->startTime = microtime(true);

        $regions = ['Wallonia', 'Brussels', 'Flanders'];

        $dataDir = base_path('data/');

        // Wipe the database.
        $this->callSilently('migrate:fresh');
        $this->line('Wiping database.');

        // Municipalities.
        foreach ($regions as $region) {
            Unzipper::unzip("{$region}Municipality");

            $this->line("Parsing <comment>municipalities</comment> of <comment>{$region}</comment>.");
            app(MunicipalityParser::class)->parse($region);

            unlink("{$dataDir}{$region}Municipality.xml");
        }

        // Postcodes.
        foreach ($regions as $region) {
            Unzipper::unzip("{$region}Postalinfo");

            $this->line("Parsing <comment>postcodes</comment> of <comment>{$region}</comment>.");
            app(PostcodeParser::class)->parse($region);

            unlink("{$dataDir}{$region}Postalinfo.xml");
        }

        // Street names.
        foreach ($regions as $region) {
            Unzipper::unzip("{$region}Streetname");

            $this->line("Parsing <comment>street names</comment> of <comment>{$region}</comment>.");
            app(StreetParser::class)->parse($region);

            unlink("{$dataDir}{$region}Streetname.xml");
        }

        // Addresses.
        foreach ($regions as $region) {
            Unzipper::unzip("{$region}Address");

            $this->line("Parsing <comment>addresses</comment> of <comment>{$region}</comment>.");
            app(AddressParser::class)->parse($region);

            unlink("{$dataDir}{$region}Address.xml");
        }


        // Display performance stats.
        $elapsed = round(microtime(true) - $this->startTime, 3);
        $memoryUsage = $this->getHumanReadableSize(memory_get_usage());
        $memoryPeakUsage = $this->getHumanReadableSize(memory_get_peak_usage());

        echo 'Elapsed time: '.$this->getHumanReadableDuration($elapsed).PHP_EOL;
        echo "Memory: {$memoryUsage} (peak {$memoryPeakUsage})".PHP_EOL;

        exit(0);
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

    /**
     * Helper to format durations.
     */
    protected function getHumanReadableDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} sec.";
        } elseif ($seconds < 3600) {
            $minutes = intval($seconds / 60);
            $seconds %= 60;

            return "{$minutes} min. {$seconds} sec.";
        }
    }
}
