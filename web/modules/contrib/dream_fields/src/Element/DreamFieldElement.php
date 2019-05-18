<?php

namespace Drupal\dream_fields\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * A render element to display a plugin tile.
 *
 * @RenderElement("dream_field")
 */
class DreamFieldElement extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'dream_field',
      '#attached' => [
        'library' => ['dream_fields/dream-field']
      ],
    ];
  }

}
