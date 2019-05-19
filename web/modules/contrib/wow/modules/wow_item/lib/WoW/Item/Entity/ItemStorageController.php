<?php

/**
 * @file
 * Definition of ItemStorageController.
 */

namespace WoW\Item\Entity;

class ItemStorageController extends \EntityAPIController {

  public function save($entity, DatabaseTransaction $transaction = NULL) {
    $entity->wow_item_name[$entity->language][0]['value'] = $entity->name;
    $entity->wow_item_description[$entity->language][0]['value'] = $entity->description;
  }

}
