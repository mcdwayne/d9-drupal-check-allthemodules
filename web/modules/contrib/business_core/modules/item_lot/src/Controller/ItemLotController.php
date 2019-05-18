<?php

namespace Drupal\item_lot\Controller;

use Drupal\cbo_item\ItemInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for ItemLot routes.
 */
class ItemLotController extends ControllerBase {

  /**
   * Provides the item_lot submission form.
   *
   * @param \Drupal\cbo_item\ItemInterface $item
   *   The item entity for the item_lot.
   *
   * @return array
   *   A item_lot submission form.
   */
  public function itemLotAddForm(ItemInterface $item) {
    $item_lot = $this->entityTypeManager()->getStorage('item_lot')->create([
      'item' => $item->id(),
    ]);

    $form = $this->entityFormBuilder()->getForm($item_lot);

    return $form;
  }

}
