<?php

namespace App;

use ZipArchive;

class Unzipper
{
    /**
     * The default name of the main ZIP archive.
     */
    protected const MAIN_ARCHIVE_NAME = 'best-full-latest.zip';

    /**
     * Unzip a ZIP archive.
     */
    public static function unzip(?string $archiveType = null)
    {
        if (is_null($archiveType) || $archiveType === static::MAIN_ARCHIVE_NAME) {
            return (new static)->unzipMainArchive();
        }

        $dataDir = base_path('data/');
        $subArchiveName = null;

        // Extract the sub-archive from the main archive.
        $zip = new ZipArchive;
        $zip->open($dataDir.'best-full-latest.zip');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            if (!str_starts_with($filename, $archiveType)) {
                continue;
            }

            $subArchiveName = $filename;
            $zip->extractTo($dataDir, $filename);
        }

        $zip->close();

        // Extract contents from the sub-archive.
        $zip = new ZipArchive;
        $zip->open($dataDir.$subArchiveName);
        $xmlFilename = $zip->getNameIndex(0);
        $zip->extractTo($dataDir);
        $zip->close();

        // Rename the extracted XML file to keep only its type.
        $matches = [];
        preg_match('#^[A-Za-z]+#', $xmlFilename, $matches);
        $newXmlFilename = $matches[0].'.xml';

        rename($dataDir.$xmlFilename, $dataDir.$newXmlFilename);

        // Delete the extracted subarchive.
        unlink($dataDir.$subArchiveName);
    }

    /**
     * Unzip the main ZIP archive, the one that contains all the other ones.
     */
    protected function unzipMainArchive(): void
    {
        $dataDir = base_path('data/');

        // Extract the contents of the archive.

        $zip = new ZipArchive;
        $zip->open($dataDir.'best-full-latest.zip');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Skip every contained file that is not a ZIP archive.
            if (!str_ends_with($filename, '.zip')) {
                continue;
            }

            $zip->extractTo($dataDir, $filename);
        }

        $zip->close();

        // Then delete the archive itself.
        // unlink($dataDir.'best-full-latest.zip');
    }
}
