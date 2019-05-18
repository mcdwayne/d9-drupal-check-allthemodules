<?php

namespace Drupal\scheduling\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\Date;

/**
 * Field handler.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("scheduling_value")
 */
class SchedulingValue extends Date {

  public $field_alias = 'scheduling_value';

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
    $value = null;

    if ($entity->hasField('scheduling_mode') && $mode = $entity->get('scheduling_mode')->value) {

      if (($mode === 'range' || $mode === 'recurring') && $entity->hasField('scheduling_value') && $values = $entity->get('scheduling_value')) {

        /** @var \Drupal\scheduling\Service\Scheduling $scheduling */
        $scheduling = \Drupal::service('scheduling');
        $expires = $scheduling->getNextStatusChangeInSeconds($mode, $values);
        if ($expires) {
          // Add a second for good measure.
          $value = REQUEST_TIME + $expires + 1;
        }
      }
    }

    $result_row = new ResultRow([
      $this->field_alias => $value
    ]);

    return parent::render($result_row);
  }
}
