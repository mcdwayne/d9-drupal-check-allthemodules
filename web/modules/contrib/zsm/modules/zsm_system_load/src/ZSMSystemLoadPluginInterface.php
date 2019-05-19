<?php

namespace Drupal\zsm_system_load;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Content entity.
 * @ingroup zsm_system_load
 */
interface ZSMSystemLoadPluginInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>