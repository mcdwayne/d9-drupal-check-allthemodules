<?php

namespace Drupal\scss_field\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a Scss render element.
 *
 * @RenderElement("scss")
 */
class Scss extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#base_type' => 'textarea',
      '#pre_render' => [
        'element.editor:preRenderTextFormat',
      ],
    ];
  }

}
