<?php
/**
 * @file SVG
 * Embedded SVG Graph as it's own document.
 * @author davidmetzler
 *
 */
namespace Drupal\forena\FrxPlugin\Document;
/**
 * Provides SVG file exports
 *
 * Exports only the first SVG element in a report. This is useful for building
 * dynamic SVG assets.
 *
 * @FrxDocument(
 *   id= "svg",
 *   name="Scaler Vector Graphics",
 *   ext="svg"
 * )
 */
class SVG extends DocumentBase {

  public function __construct() {
    $this->content_type = 'image/svg+xml';
  }

  public function header() {
    $this->write_buffer = '';
    $this->headers = [];
    $this->headers['Content-Type'] = $this->content_type;
    $this->headers['Cache-Control'] =  'must-revalidate';
  }

  public function flush() {
    $output = '';
    $svg = NULL;
    $doc = new \DomDocument();
    $xml_body = '<html><body>' . $this->write_buffer . '</body></html>';
    $doc->formatOutput=FALSE;
    $doc->strictErrorChecking = FALSE;
    libxml_use_internal_errors(true);
    $doc->loadXML($xml_body);
    libxml_clear_errors();
    $xml = simplexml_import_dom($doc);

    // Extract first SVG from document.
    $xml->registerXPathNamespace('__empty_ns', 'http://www.w3.org/2000/svg');
    if ($xml){
      $svg = $xml->xpath('//__empty_ns:svg');
      if (!$svg) $svg = $xml->xpath('//svg');
    }

    if ($svg) {
      $output .= $svg[0]->asXML();
    }
    else {
      $output = '<svg/>';
    }
    // Add in namespaces
    if (!strpos( $output, 'xmlns') ) {
      $output = str_replace('<svg', '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"', $output);
    }
    return $output;
  }

}