<?php

namespace Drupal\bigcommerce\Plugin\Field\FieldType;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Represents an entity BigCommerce ID field.
 */
class BigCommerceIdFieldItemList extends FieldItemList {
  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $value = ['value' => NULL];

    if ($sync = $this->getSetting('bigcommerce_sync')) {
      /** @var \Drupal\migrate\Plugin\Migration $migration */
      $migration = \Drupal::service('plugin.manager.migration')->createInstance($sync['plugin']);
      $lookup = $migration->getIdMap()->lookupSourceId([
        $sync['id'] => $this->getEntity()->id(),
      ]);
      // If the ID is found in the map set the computed value.
      if (isset($lookup['id'])) {
        $value['value'] = (int) $lookup['id'];
      }
    }

    $this->list[0] = $this->createItem(0, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultAccess($operation = 'view', AccountInterface $account = NULL) {
    if ($operation == 'view') {
      return AccessResult::allowed();
    }
    // Read only.
    return AccessResult::forbidden();
  }

}
