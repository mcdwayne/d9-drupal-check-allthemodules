<?php

namespace Drupal\cbo_item;

use Drupal\cbo\CboConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of item category entities.
 *
 * @see \Drupal\cbo_item\Entity\ItemCategory
 */
class ItemCategoryListBuilder extends CboConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $operations['list'] = [
      'title' => t('List'),
      'weight' => 0,
      'url' => Url::fromRoute('item.item_category_item', [
        'item_category' => $entity->id(),
      ]),
    ];

    return $operations;
  }

}
