<?php

namespace Drupal\openimmo;

/**
 * Fetches a data from OpenImmo server.
 */
class OpenImmoFetcher implements OpenImmoFetcherInterface {

  /**
   * Constructs a OpenImmoFetcher.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function fetchOpenImmoData(array $source) {
    $data = '';

    if ($source['feed_type'] == 'file') {
      // Example 'sites/default/files/openimmo/files/transfer.zip';.
      $data = readXmlFile($source['id'], $source['file_path']);
    }
    elseif ($source['feed_type'] == 'ftp') {
      // todo: get a openimmoxml.zip file by ftp.
    }

    return $data;
  }

  /**
   * Read XML.
   */
  public function readXmlFile($source_id, $zip_file) {
    $xml_array = '';

    $extract_directory = $this->getExtractDirectory($source_id);

    // todo: add check if in $zip_file is file then use it, if it is
    // just directory then load a file with extension *.zip.
    $zip = new \ZipArchive();
    $res = $zip->open($zip_file);
    if ($res === TRUE) {
      $zip->extractTo($extract_directory);
      $zip->close();
    }

    $xml_files = glob($extract_directory . '/*.xml');

    try {
      $xml_file = fopen($xml_files[0], "r");
      $xml_data = fread($xml_file, filesize($xml_files[0]));
      fclose($xml_file);

      // To object
      // $data = new \SimpleXMLElement($xml_data);
      // To array.
      $xml       = simplexml_load_string($xml_data, 'SimpleXMLElement', LIBXML_NOCDATA);
      $xml_array = json_decode(json_encode((array) $xml), TRUE);
    }
    catch (RequestException $exception) {
      watchdog_exception('openimmo', $exception);
    }

    return $xml_array;
  }

  /**
   * Get directory.
   */
  public function getExtractDirectory($source_id, $create = TRUE) {

    $directory = 'temporary://openimmo-extraction-' . $source_id;
    if ($create && !file_exists($directory)) {
      mkdir($directory);
    }

    return $directory;
  }

}
