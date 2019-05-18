<?php

namespace Drupal\entity_counter\Plugin\EntityCounterRenderer;

use Drupal\entity_counter\Plugin\EntityCounterRendererBase;

/**
 * Adds plain renderer to entity counters.
 *
 * @EntityCounterRenderer(
 *   id = "plain",
 *   label = @Translation("Plain"),
 *   description = @Translation("Render the entity counter value as a plain text string.")
 * )
 */
class Plain extends EntityCounterRendererBase {

  /**
   * {@inheritdoc}
   */
  public function render(array &$element) {
    // @TODO Add support to child elements.
    // $element[] = ['#plain_text' => $this->getEntityCounter()->getValue()];
    $element['#counter_value'] = $this->getEntityCounter()->getValue() * $element['#renderer_settings']['ratio'];
    if (!empty($element['#renderer_settings']['round'])) {
      $element['#counter_value'] = round($element['#counter_value'], 0, $element['#renderer_settings']['round']);
    }
  }

}
