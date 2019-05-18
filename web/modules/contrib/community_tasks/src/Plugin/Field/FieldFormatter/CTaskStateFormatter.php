<?php

namespace Drupal\community_tasks\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the Community Task state field formatter.
 *
 * @FieldFormatter(
 *   id = "ctask_state",
 *   label = @Translation("Community task state"),
 *   field_types = {
 *     "list_string"
 *   }
 * )
 *
 */
class CTaskStateFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return [
      '#type' => 'community_task_state',
      '#nid' => $items->getEntity()->id()
    ];
  }
}
