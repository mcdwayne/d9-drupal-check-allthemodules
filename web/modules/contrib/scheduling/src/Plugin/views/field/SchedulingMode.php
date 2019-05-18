<?php

namespace Drupal\scheduling\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\Boolean;

/**
 * Field handler.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("scheduling_mode")
 */
class SchedulingMode extends Boolean {

  public $field_alias = 'scheduling_mode';

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $values->_entity;
    $value = FALSE;

    if ($entity->hasField('scheduling_mode') && $mode = $entity->get('scheduling_mode')->value) {

      if (($mode === 'range' || $mode === 'recurring') && $entity->hasField('scheduling_value') && $values = $entity->get('scheduling_value')) {

        /** @var \Drupal\scheduling\Service\Scheduling $scheduling */
        $scheduling = \Drupal::service('scheduling');
        $value = $scheduling->getStatus($mode, $values, TRUE);
      } else {
        $value = $mode === 'published';
      }
    }

    $result_row = new ResultRow([
      $this->field_alias => $value
    ]);

    return parent::render($result_row);
  }
}
