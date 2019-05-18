<?php

namespace Drupal\cbo_item;

use Drupal\cbo_item\Entity\ItemType;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides dynamic permissions for items of different types.
 */
class ItemPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of item type permissions.
   *
   * @return array
   *   The item type permissions.
   */
  public function itemTypePermissions() {
    $perms = [];
    // Generate item permissions for all item types.
    foreach (ItemType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of item permissions for a given item type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(ItemType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id item" => [
        'title' => $this->t('%type_name: Create new item', $type_params),
      ],
      "edit $type_id item" => [
        'title' => $this->t('%type_name: Edit item', $type_params),
      ],
      "delete $type_id item" => [
        'title' => $this->t('%type_name: Delete item', $type_params),
      ],
    ];
  }

}
