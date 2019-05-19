<?php

namespace Drupal\zsm;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a zsm_core entity.
 * @ingroup zsm
 */
interface ZSMCoreInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>