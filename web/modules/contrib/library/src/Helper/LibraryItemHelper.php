<?php

namespace Drupal\library\Helper;

use Drupal\library\Entity\LibraryAction;
use Drupal\library\Entity\LibraryItem;
use Drupal\node\Entity\Node;

/**
 * Helper class for static functions.
 */
class LibraryItemHelper {

  /**
   * Find by barcode.
   *
   * @param string $barcode
   *   Barcode to search.
   *
   * @return bool|LibraryItem
   *   Item or FALSE.
   */
  public static function findByBarcode($barcode) {
    $items = [];
    $results = \Drupal::entityQuery('library_item')
      ->condition('barcode', $barcode)
      ->execute();
    foreach ($results as $result) {
      $items[] = LibraryItem::load($result);
    }
    if (count($items) == 1 && $items[0] instanceof LibraryItem) {
      return $items[0];
    }
    else {
      return FALSE;
    }
  }

  /**
   * Update item availability.
   *
   * @param int $item
   *   Item ID.
   * @param string $action
   *   Action name to perform, e.g. 'check_in'.
   */
  public static function updateItemAvailability($item, $action) {
    $action = LibraryAction::load($action);
    $item = LibraryItem::load($item);
    if ($action->action() == LibraryAction::CHANGE_TO_UNAVAILABLE) {
      $item->set('library_status', LibraryItem::ITEM_UNAVAILABLE);
      $item->save();
    }
    elseif ($action->action() == LibraryAction::CHANGE_TO_AVAILABLE) {
      $item->set('library_status', LibraryItem::ITEM_AVAILABLE);
      $item->save();
    }
  }

  /**
   * Compute the due date.
   *
   * Fetches due date from field definition in content type.
   *
   * @param string $action
   *   Action name to perform, e.g. 'check_in'.
   * @param int $nid
   *   Node ID.
   *
   * @return int
   *   Due date timestamp.
   */
  public static function computeDueDate($action, $nid) {
    $action = LibraryAction::load($action);

    if ($action->action() != LibraryAction::CHANGE_TO_AVAILABLE) {
      $due = 30;
      $node = Node::load($nid);
      $bundle_fields = \Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions('node', $node->getType());
      foreach ($bundle_fields as $field) {
        if ($field->getType() == 'library_item_field_type') {
          $due = $field->getSetting('due_date');
        }
      }
      $due = strtotime('today') + (86400 * $due);
    }
    else {
      $due = 0;
    }
    return $due;
  }

}
