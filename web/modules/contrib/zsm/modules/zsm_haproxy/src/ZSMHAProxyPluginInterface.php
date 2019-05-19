<?php

namespace Drupal\zsm_haproxy;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Contact entity.
 * @ingroup zsm_haproxy
 */
interface ZSMHAProxyPluginInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>