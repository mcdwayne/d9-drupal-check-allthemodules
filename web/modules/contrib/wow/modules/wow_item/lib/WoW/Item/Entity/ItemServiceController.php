<?php

/**
 * @file
 * Definition of ItemServiceController.
 */

namespace WoW\Item\Entity;

use WoW\Core\Entity\EntityServiceController;
use WoW\Core\Response;
use WoW\Core\ServiceInterface;

class ItemServiceController extends EntityServiceController {

  /**
   * The item API provides detailed item information.
   *
   * @param Item $item
   *   The item to fetch.
   *
   * @return Response
   *   A response object.
   */
  public function fetch(Item $item) {
    return $this->service($item->region)
      ->newRequest("item/$item->id")
        ->setLocale($item->language)
        ->onResponse()
          ->mapFunction(200, 'WoW\Item\Entity\merge', array($item))
          ->execute();
  }

}

/**
 * Callback; Merges the response with the entity.
 */
function merge(ServiceInterface $service, Response $response, Item $entity) {
  // TODO: is there an API for this ?
  foreach ($response->getData() as $key => $value) {
    if ($key == 'name') {
      $entity->wow_item_name[$entity->language][0]['value'] = $value;
    }
    else if ($key == 'description') {
      $entity->wow_item_description[$entity->language][0]['value'] = $value;
    }
    else {
      $entity->{$key} = $value;
    }
  }
}
