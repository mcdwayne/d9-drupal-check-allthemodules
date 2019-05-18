<?php

namespace Drupal\core_extend\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines a role entity base class.
 */
abstract class RoleEntityBase extends ConfigEntityBase implements ConfigEntityInterface, RoleEntityInterface {
  use RoleEntityTrait;

}
