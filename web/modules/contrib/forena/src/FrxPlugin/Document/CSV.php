<?php
/**
 * @file
 * Implements \Drupal\forena\FrxPlugin\Document\CSV
 *
 */
namespace Drupal\forena\FrxPlugin\Document;

/**
 * Provides CSV file exports
 *
 * @FrxDocument(
 *   id= "csv",
 *   name="Comma Separated Values",
 *   ext="csv"
 * )
 */
class CSV extends DocumentBase {

  public function __construct() {
    $this->content_type = 'application/csv';
  }

  public function flush() {
    $doc = $this->write_buffer;
    $dom = new \DOMDocument();
    $dom->strictErrorChecking = FALSE;
    $xmlBody = '<?xml version="1.0" encoding="UTF-8"?>' . $doc;
    libxml_use_internal_errors(TRUE);
    @$dom->loadHTML($xmlBody);
    libxml_clear_errors();
    /** @var \SimpleXMLElement $xml */
    $xml = simplexml_import_dom($dom);

    $output = '';
    $rows = array();
    if (!empty($xml)) {
      $rows = $xml->xpath('//tr');
    }
    $rowspans = array();
    if ($rows) foreach ($rows as $row) {
      $c = 0;
      $line = '';
      
      /** @var \SimpleXMLElement $column */
      foreach ($row as $column) {
        $c++;
        if (@$rowspans[$c]) {
          $cont = TRUE;
          while ($rowspans[$c] && $cont) {
            $rowspans[$c]--;
            $output .= ',';
            $c++;
          }
        }
        $value = $column->asXML();
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        $value = str_replace('"', '""', $value);
        $value = str_replace(array("\n"), '', $value);
        $value =  strpos($value, ',')!==FALSE || strpos($value, '"') !==FALSE ?  '"' . $value . '",' : "$value,";
        $line .= $value;
        // Add Column span elements
        if ((int)$column['colspan'] > 1) {
          for ($i=2; $i<=(int)$column['colspan']; $i++) {
            $c++;
            $line .= ',';
          }
        }
        // Check to see if we have some rowspans that we need to save
        if ((int)$column['rowspan'] > 1) {
          $rowspans[$c] = (int)$column['rowspan'] - 1;
        }
      }
      // Trim off the last comma so we don't put in an extra null column
      $line = substr($line,0,-1);
      $output .= "$line\n";
    }
    return $output;
  }

}

