<?php

/**
 * @file
 * Definition of Item.
 */

namespace WoW\Item\Entity;

use WoW\Core\Entity\Entity;

/**
 * Defines the wow_item entity class.
 */

class Item extends Entity {

  /**
   * The item ID.
   *
   * @var integer
   */
  public $id;

  /**
   * The item language.
   *
   * @var string
   */
  public $language;

  /**
   * The item quality.
   *
   * @var integer
   */
  public $quality;

  /**
   * The icon of this item.
   *
   * @var string
   */
  public $icon;

  /**
   * The item API provides detailed item information.
   *
   * @return Response
   *   A response object.
   */
  public function fetch() {
    return wow_service_controller($this->entityType)->fetch($this);
  }

}
