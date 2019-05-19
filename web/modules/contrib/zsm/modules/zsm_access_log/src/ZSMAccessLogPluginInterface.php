<?php

namespace Drupal\zsm_access_log;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Contact entity.
 * @ingroup zsm_access_log
 */
interface ZSMAccessLogPluginInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>