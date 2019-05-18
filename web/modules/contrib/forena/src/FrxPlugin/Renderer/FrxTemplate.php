<?php
/**
 * @File
 * Implements FrxTemplate renderer
 * Class FrxTemplate
 * @package Drupal\forena\FrxPlugin\Renderer
 */
namespace Drupal\forena\FrxPlugin\Renderer;
use Drupal\forena\FrxAPI;



/**
 * FrxAPI Template renderer
 *
 * @FrxRenderer(id = "FrxTemplate")
 */
class FrxTemplate extends RendererBase {
  use FrxAPI;
  public function render() {
    $output = '';
    $template = $this->innerXML($this->reportNode);

    // Update the template
    if ($template) {
      $output = $this->report->replace($template);
    }
    //remove CDATA stuff
    $output = str_replace('<![CDATA[', '', $output);
    $output = str_replace(']]>', '', $output);

    //token replace items in clause body
    $output = $this->report->replace($output);
    return $output;
  }
}