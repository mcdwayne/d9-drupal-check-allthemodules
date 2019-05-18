<?php
/**
 * @file
 * Implements a title renderer
 * @author metzlerd
 *
 */
namespace Drupal\forena\FrxPlugin\Renderer;
/**
 * Title Renderer
 *
 * @FrxRenderer(id = "FrxTitle")
 */
class FrxTitle extends RendererBase {
  public function render() {
    $html = $this->innerXML($this->reportNode);
    $html = $this->report->replace($html);
    $this->report->title = $html;
    return '';
  }
}
