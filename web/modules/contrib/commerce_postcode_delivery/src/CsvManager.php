<?php

namespace Drupal\commerce_postcode_delivery;

use Drupal\file\Entity\File;

/**
 * Class CsvManager.
 */
class CsvManager {

  /**
   * Validate CSV file.
   *
   * @param string $uri
   *   File uri.
   *
   * @return bool
   *   TRUE or FALSE based on the column headers.
   */
  public function validateCsvInputFile($uri) {
    $headers = $this->getCsvHeaders();
    $headers = array_map('strtolower', $headers);
    sort($headers);

    $rows = array_map('str_getcsv', file($uri));
    $file_headers = array_filter(array_shift($rows));
    $file_headers = array_map('strtolower', $file_headers);
    sort($file_headers);

    return $headers == $file_headers;
  }

  /**
   * Read CSV file containing postal shipping rates.
   *
   * @param string $uri
   *   File uri.
   *
   * @return array
   *   Shipping rates in array format.
   */
  public function readCsvInputFile($uri) {
    $csv = [];
    $rows = array_map('str_getcsv', file($uri));
    $header = array_shift($rows);
    $header = array_map('strtolower', $header);

    foreach ($rows as $row) {
      if (!empty($row[0])) {
        $row = array_map('strtoupper', $row);
        $csv[] = array_filter(array_combine($header, $row));
      }
    }

    return $csv;
  }

  /**
   * Expected CSV file headers.
   *
   * @return array
   *   Required file headers values.
   */
  public function getCsvHeaders() {
    return [
      'postal_code',
      'shipping_rate',
      'currency_code',
    ];
  }

  /**
   * Example sample data.
   *
   * @return array
   *   Sample data in array format.
   */
  public function getSampleData() {
    return [
      ['V1G', '35', 'CAD'],
      ['V1J', '35', 'CAD'],
      ['V8C', '35', 'CAD'],
      ['V1N', '30', 'CAD'],
      ['V1C', '30', 'CAD'],
      ['V1S', '30', 'CAD'],
      ['V1B', '25', 'CAD'],
      ['V4T', '25', 'CAD'],
      ['V4T', '25', 'CAD'],
      ['V1T', '25', 'CAD'],
    ];
  }

  /**
   * Description as an example CSV file.
   *
   * @return mixed
   *   An example to create a CSV file.
   */
  public function getCsvDescription() {
    $header = $this->getCsvHeaders();
    $data = $this->getSampleData();

    $sample['sample_csv'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $data,
    ];

    return render($sample);
  }

  /**
   * Get currently uploaded rates from the CSV file.
   *
   * @param int $fid
   *   File ID.
   *
   * @return mixed
   *   A rate list from uploaded CSV file.
   */
  public function getCurrentUploadedRates($fid) {
    $data = $rates = [];
    $header = $this->getCsvHeaders();

    if (!empty($fid) && $fid != 0 && $fid != NULL) {
      $file = File::load($fid);
      $rows = array_map('str_getcsv', file($file->getFileUri()));
      array_shift($rows);

      foreach ($rows as $row) {
        if (!empty($row[0])) {
          $data[] = array_filter($row);
        }
      }
      $rates['uploaded_rates'] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $data,
      ];
    }

    return render($rates);
  }

}
