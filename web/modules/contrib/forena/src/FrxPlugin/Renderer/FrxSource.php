<?php
/**
 * @file FrxSource
 * Displays source of FrxAPI Report rather than rendering
 * Look at the FrxRender class to see a full list of
 * properties that can be used here.
 */
namespace Drupal\forena\FrxPlugin\Renderer;
/**
 * FRX Source Code Renderer
 *
 * @FrxRenderer(id = "FrxSource")
 */
class FrxSource extends RendererBase {
  public function render() {
    $node = $this->reportNode;
    $html = $node->asXML();
    $html = str_replace(' frx:renderer="FrxSource"', '', $html);
    $html = str_replace('<html>', '<html xmlns:frx="urn:FrxReports">', $html);
    $output = "<pre>\n" . htmlspecialchars($html) . "\n</pre>";
    return $output;
  }
}