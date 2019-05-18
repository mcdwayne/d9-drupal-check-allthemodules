<?php

namespace Drupal\chartjs_api\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element to display a graphjs.
 *
 * @RenderElement("chartjs_api")
 */
class ChartjsApiTheming extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#attached' => [
        'library' => [
          'chartjs_api/chartjs',
        ],
      ],
      '#data' => [],
      '#graph_type' => NULL,
      '#id' => NULL,
      '#options' => [],
      '#plugins' => [],
      '#pre_render' => [
        [$class, 'preRenderChartjsApiTheming'],
      ],
      '#theme' => 'chartjs_api',
    ];
  }

  /**
   * Element pre render callback.
   */
  public static function preRenderChartjsApiTheming($element) {
    if (!empty($element['#plugins'])) {
      $element['#attached']['library'][] = 'chartjs_api/chartjs_plugins';
    }
    $element['#attached']['drupalSettings']['chartjs'][$element['#id']] = [
      'id' => $element['#id'],
      'data' => $element['#data'],
      'options' => $element['#options'],
      'plugins' => $element['#plugins'],
    ];

    return $element;
  }

}
