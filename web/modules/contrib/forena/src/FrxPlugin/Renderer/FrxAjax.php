<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/21/16
 * Time: 8:32 PM
 */

namespace Drupal\forena\FrxPlugin\Renderer;

/**
 * Crosstab Renderer
 *
 * @FrxRenderer(id = "FrxAjax")
 *
 */
class FrxAjax extends RendererBase {

  public function render() {
    $text = $this->innerXML($this->reportNode);
    $command = $this->mergedAttributes();
    $command['text'] = $text; 
    $event = $this->extract('event', $command);
    if (strpos($event, 'pre')===0) {
      $event = 'pre';
    }
    else {
      $event = 'post';
    }
    $this->getDocument()->addAjaxCommand($command, $event);
    return '';
  }
}