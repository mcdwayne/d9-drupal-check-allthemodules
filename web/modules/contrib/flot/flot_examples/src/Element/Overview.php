<?php

namespace Drupal\flot_examples\Element;

use Drupal\flot\Element\Flot;

/**
 * Provides a flot render element.
 *
 * @RenderElement("flot_overview")
 */
class Overview extends Flot {

  /**
   * Add elements to the parent render array.
   */
  public function getInfo() {
    $output = parent::getInfo();
    $output['#options2'] = NULL;
    $output['#id2'] = 'overview';

    return $output;
  }

  /**
   * Add these new variables to drupalSettings.
   */
  public static function preRenderPlot($element) {
    $element = parent::preRenderPlot($element);
    // Create a collection of the data and options for the javascript files.
    $drupalsettings = $element['#attached']['drupalSettings'];
    $drupalsettings['flot'][$element['#id2']] = [
      'options' => $element['#options2'],
    ];
    $element['#attached']['drupalSettings'] = $drupalsettings;
    return $element;
  }

}
