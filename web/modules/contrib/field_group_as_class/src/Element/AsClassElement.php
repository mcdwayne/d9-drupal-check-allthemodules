<?php

namespace Drupal\field_group_as_class\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a class from field render element for Field Groups.
 *
 * @RenderElement("field_group_as_class")
 */
class AsClassElement extends RenderElement {

  static public $element;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {

    $class = get_class($this);

    return [
      '#pre_render' => [
        [$class, 'preRenderAsClass'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Prerender for AsClass element.
   */
  public static function preRenderAsClass($element) {

    // Add classes.
    if (!empty($element['#options']['attributes']['class'])) {
      $element['#attributes']['class'] = $element['#options']['attributes']['class'];
    }

    // Add field_class.
    if (!empty($element['#field_class'])) {
      $element['#attributes']['class'][] = $element['#field_class'];
    }

    return $element;
  }

}
