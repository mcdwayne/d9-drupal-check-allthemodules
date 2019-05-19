<?php

namespace Drupal\zsm_memswap;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Contact entity.
 * @ingroup zsm_memswap
 */
interface ZSMMemSwapPluginInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>