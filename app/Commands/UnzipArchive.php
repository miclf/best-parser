<?php

namespace App\Commands;

use App\Unzipper;
use LaravelZero\Framework\Commands\Command;

class UnzipArchive extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'unzip-archive {type?}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Decompress the ZIP archive downloaded from BOSA';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // If an archive type is provided, unzip that specific archive.
        if ($type = $this->argument('type')) {
            Unzipper::unzip($type);

            $this->info("Archive {$type} unzipped.");

            exit(0);
        }

        // Otherwise unzip the main archive.
        Unzipper::unzip();

        $this->info('Archive unzipped.');

        exit(0);
    }
}
