<?php
/**
 * @file FrxXML
 * Just render the XML source data.
 * Look at the FrxRender class to see a full list of
 * properties that can be used here.
 */
namespace Drupal\forena\FrxPlugin\Renderer;
use Drupal\forena\Context\DataContext;
use Drupal\forena\FrxAPI;

/**
 * XML Data Renderer
 *
 * @FrxRenderer(id = "FrxXML")
 */
class FrxXML extends RendererBase {
  use FrxAPI; 
  public function render() {
    $output = '';
    $node = $this->reportNode;
    $options = $this->mergedAttributes();
    if (isset($options['context'])) {
      $xml = $this->getDataContext($options['context']);
    }
    else {
      $xml = $this->currentDataContext();
    }
    if (is_array($xml)) $xml = DataContext::arrayToXml($xml);
    if ($xml && is_callable(array($xml, 'asXML')))  {
        $dom = dom_import_simplexml($xml);
        $dom->ownerDocument->formatOutput = TRUE;
        $dom->ownerDocument->preserveWhiteSpace = TRUE;
        $output = $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);
    }
    if ($this->report->format != 'xml') $output = '<pre>' . htmlspecialchars($output) . '</pre>';
    return $output;
  }
}