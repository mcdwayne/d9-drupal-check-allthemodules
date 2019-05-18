<?php

namespace Drupal\dhis\Util;

use Drupal\Core\File\FileSystem;

class CsvHandler
{
    private $file_location;

    public function __construct(FileSystem $file_system)
    {
        $this->file_location = $file_system->realpath("public://");
    }

    public function createCsv(array $header, array $data)
    {
        array_unshift($data, $header);
        $file_realpath = $this->file_location . "/data.csv";
        $file = fopen($file_realpath, "w");

        foreach ($data as $row) {
            fputcsv($file, $row, ',');
        }
        fclose($file);
    }

    public function readCsv($file)
    {
        $content = array();
        $path = $this->file_location . '\\' . $file;
        if (file_exists($path) == 1) {
            $file = fopen($path, "r");
            $line_count = 0;
            while (($line = fgetcsv($file)) !== false) {
                if ($line_count != 0) {
                    $content[] = $line[0];
                }
                $line_count++;
            }
            fclose($file);
        }
        return $content;
    }
}