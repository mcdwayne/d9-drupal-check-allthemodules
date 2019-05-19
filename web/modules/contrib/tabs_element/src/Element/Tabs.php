<?php

namespace Drupal\tabs_element\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a tabs element.
 *
 * @RenderElement("tabs")
 */
class Tabs extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#tabs' => '',
      '#content' => NULL,
      '#pre_render' => [
        [static::class, 'preRender'],
      ],
    ];
  }

  /**
   * Renders the tabs into markup.
   *
   * @param array $element
   *   The tabs element.
   *
   * @return array
   *   The renderable array.
   */
  public static function preRender(array $element) {
    $element['#theme'] = 'tabs_element';
    $element['#tab_id'] = 'tabid-' . uniqid();
    $element['#attached']['library'] = ['tabs_element/tabs'];
    return $element;
  }

}
