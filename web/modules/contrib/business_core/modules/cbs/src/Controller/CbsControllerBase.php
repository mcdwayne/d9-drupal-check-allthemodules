<?php

namespace Drupal\cbs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Utility base class for business solution controllers.
 */
abstract class CbsControllerBase extends ControllerBase {

  /**
   * Return the implementation status render array.
   */
  protected function _implementationStatus(array $items) {
    $output = [
      '#theme' => 'item_list',
      '#wrapper_attributes' => [
        'class' => [
          'implementation-status',
        ],
      ],
      '#items' => $items,
      '#attached' => [
        'library' => [
          'cbs/implementation_status',
        ],
      ],
    ];

    return $output;
  }

  /**
   * Check entity table data to guess implementation status.
   */
  protected function checkEntity($step, $title, $entity_type_id, array $properties = []) {
    $query = $this->entityTypeManager()->getStorage($entity_type_id)->getQuery();
    foreach ($properties as $key => $value) {
      $query->condition($key, $value);
    }
    $count = $query->count()->execute();
    if ($count) {
      $item = $this->t('Step @step <a href=":url">@title</a>', [
        '@step' => $step,
        ':url' => Url::fromRoute('entity.' . $entity_type_id . '.collection')->toString(),
        '@title' => $title,
      ]);
    }
    else {
      $text = $this->t('Step @step <a href=":url">@title</a> (Not Finished)', [
        '@step' => $step,
        ':url' => Url::fromRoute('entity.' . $entity_type_id . '.collection')->toString(),
        '@title' => $title,
      ]);
      $item = [
        '#markup' => $text,
        '#wrapper_attributes' => [
          'class' => [
            'not-finished',
          ],
        ],
      ];
    }
    return $item;
  }

}
