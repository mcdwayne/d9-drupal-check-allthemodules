<?php

namespace Drupal\mimedetect\Plugin\MimeDetector;

use Drupal\mimedetect\MimeDetectorBase;

/**
 * Provides a CSV MIME detector.
 *
 * @MimeDetector(
 *   id = "csv_mimedetector",
 *   description = @Translation("Comma separated value (CSV) MIME type detector."),
 *   filename_extensions = {"csv"}
 * )
 */
class CsvMimeDetector extends MimeDetectorBase {

  /**
   * {@inheritdoc}
   */
  public function detect($path) {
    if (($handler = @fopen($path, 'r')) !== FALSE) {
      // Check first line, it must be printable text.
      $first_line = fgets($handler, 16384);
      if ($first_line && strlen($first_line) && ctype_print(str_replace(["\n", "\r"], '', $first_line))) {
        rewind($handler);

        // Read the first two lines, they must have the same number of columns.
        $columns = [];
        while (($data = fgetcsv($handler)) !== FALSE && count($columns) < 2) {
          $columns[] = count($data);
        }
        if (count($columns) == 1 || $columns[0] == $columns[1]) {
          return 'text/csv';
        }
      }
    }

    return NULL;
  }

}
