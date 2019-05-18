<?php

namespace Drupal\commerce_wishlist;

use Drupal\views\EntityViewsData;

/**
 * Provides views data for wishlist items.
 */
class WishlistItemViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Unset the default purchasable entity relationship.
    // It does not work properly, the target type it is not defined.
    unset($data['commerce_wishlist_item']['purchasable_entity']['relationship']);
    // Provide a relationship for each entity type found.
    foreach (commerce_wishlist_get_purchasable_entity_types() as $entity_type) {
      $data['commerce_wishlist_item'][$entity_type->id()] = [
        'relationship' => [
          'title' => $entity_type->getLabel(),
          'help' => t('The purchasable entity @entity_type.', ['@entity_type' => $entity_type->getLowercaseLabel()]),
          'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
          'base field' => $entity_type->getKey('id'),
          'relationship field' => 'purchasable_entity',
          'id' => 'standard',
          'label' => $entity_type->getLabel(),
        ],
      ];
    }

    $data['commerce_wishlist_item']['edit_quantity']['field'] = [
      'title' => t('Wishlist quantity text field'),
      'help' => t('Adds a text field for editing the quantity.'),
      'id' => 'commerce_wishlist_item_edit_quantity',
    ];

    $data['commerce_wishlist_item']['remove_button']['field'] = [
      'title' => t('Remove button'),
      'help' => t('Adds a button for removing the wishlist item.'),
      'id' => 'commerce_wishlist_item_remove_button',
    ];

    $data['commerce_wishlist_item']['move_to_cart']['field'] = [
      'title' => t('Move/copy to cart button'),
      'help' => t('Adds a button for moving or copying the wishlist item to the shopping cart.'),
      'id' => 'commerce_wishlist_item_move_to_cart',
    ];

    return $data;
  }

}
