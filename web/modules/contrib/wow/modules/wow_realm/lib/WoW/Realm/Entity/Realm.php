<?php

/**
 * @file
 * Definition of Realm.
 */

namespace WoW\Realm\Entity;

use WoW\Core\Entity\Entity;
use WoW\Core\Entity\EntityServiceController;

use WoW\Core\Response;

/**
 * Defines the wow_realm entity class.
 */
class Realm extends Entity {

  /**
   * The realm ID.
   *
   * @var integer
   */
  public $rid;

  /**
   * The realm type: pvp, pve, rp or rppvp.
   *
   * @var string
   */
  public $type;

  /**
   * The realm locale.
   *
   * @var string
   */
  public $locale;

  /**
   * The population density: low, medium, or high.
   *
   * @var string
   */
  public $population;

  /**
   * Whether the server has queue to enter.
   *
   * @var integer
   */
  public $queue;

  /**
   * Whether the server is running(1) or not(0).
   *
   * @var integer
   */
  public $status;

  /**
   * The realm region.
   *
   * @var string
   */
  public $region;

  /**
   * The realm name.
   *
   * @var string
   */
  public $name;

  /**
   * The realm machine name (slug).
   *
   * @var string
   */
  public $slug;

  /**
   * The realm battle group.
   *
   * @var string
   */
  public $battlegroup;

  /**
   * Realm APIs currently provide realm status information.
   *
   * @return Response
   *   A response object.
   */
  public function fetch() {
    return wow_service_controller($this->entityType)->fetch($this);
  }

}
