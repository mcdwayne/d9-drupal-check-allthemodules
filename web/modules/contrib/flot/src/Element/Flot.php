<?php

namespace Drupal\flot\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a flot render element.
 *
 * @RenderElement("flot")
 */
class Flot extends RenderElement {

  /**
   * Define the available options for our new render element.
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderPlot'],
      ],
      '#attached' => array(),
      '#theme' => 'flot_element',
      '#data' => NULL,
      '#options' => NULL,
      '#id' => NULL,
      '#resizable' => FALSE,
    ];
  }

  /**
   * PreRender Function.
   *
   * Before rendering, we will scan the element's variables to see which
   * libraries need to be included. Add the appropriate libraries, and add
   * the necessary variables to drupalSettings so that the JS can access them.
   */
  public static function preRenderPlot($element) {
    $element['#id'] = $element['#id'] === NULL ? Html::getUniqueId('flot-chart') : $element['#id'];
    // Initaialize with current library list in case some have already
    // been specified.
    $libraries = isset($element['#attached']['library']) ? $element['#attached']['library'] : [];    

    // Add the main flot library.
    $libraries[] = 'flot/flot';

    // Add if user want to resize the chart.
    if ($element['#resizable']) {
      $libraries[] = 'flot/resize';
    }

    // Examine the options variable to see what js files we need to include.
    if (isset($element['#options']['xaxis']['mode']) && $element['#options']['xaxis']['mode'] == 'categories') {
      $libraries[] = 'flot/categories';
    }

    // Examine all the xaxes elements to see if any are time-series.
    $time_axis = FALSE;
    if (isset($element['#options']['xaxes']) && is_array($element['#options']['xaxes'])) {
      foreach ($element['#options']['xaxes'] as $xaxis) {
        if (isset($xaxis['mode']) && $xaxis['mode'] == 'time') {
          $time_axis = TRUE;
        }
      }
    }
    if ($time_axis || (isset($element['#options']['xaxis']['mode']) && $element['#options']['xaxis']['mode'] == 'time')) {
      $libraries[] = 'flot/time';
    }

    // Check if the selection option is set.
    if (isset($element['#options']['selection'])) {
      $libraries[] = 'flot/selection';
    }

    // Check if the crosshair option is set.
    if (isset($element['#options']['crosshair'])) {
      $libraries[] = 'flot/crosshair';
    }

    // Check if the pan or zoom options are set.
    if (isset($element['#options']['pan']) || isset($element['#options']['zoom'])) {
      $libraries[] = 'flot/navigate';
    }

    // Check if the stack option is set.
    if (isset($element['#options']['series']['stack'])) {
      $libraries[] = 'flot/stack';
    }

    // Check if the images option is set.
    if (isset($element['#options']['series']['images'])) {
      $libraries[] = 'flot/images';
    }

    // Check if the chart is a pie chart.
    if (isset($element['#options']['series']['pie'])) {
      $libraries[] = 'flot/pie';
    }

    // Check if the canvas option is set.
    if (isset($element['#options']['canvas']) && $element['#options']['canvas']) {
      $libraries[] = 'flot/canvas';
    }

    // Examine the data to see what js files we need to include.
    $found_symbol = FALSE;
    foreach ($element['#data'] as $series) {
      if (isset($series['points']['symbol']) && !$found_symbol) {
        $libraries[] = 'flot/symbol';
        $found_symbol = TRUE;
      }
      if (isset($series['threshold'])) {
        $libraries[] = 'flot/threshold';
      }
      if (isset($series['fillBetween'])) {
        $libraries[] = 'flot/fillbetween';
      }
      if (isset($series['points']['errorbars'])) {
        $libraries[] = 'flot/errorbars';
      }
    }

    // Create a collection of the data and options for the javascript files.
    $drupalsettings['flot'][$element['#id']] = [
      'data' => $element['#data'],
      'options' => $element['#options'],
    ];
    $element['#attached'] = [
      'drupalSettings' => $drupalsettings,
      'library' => $libraries,
    ];
    return $element;
  }

}
