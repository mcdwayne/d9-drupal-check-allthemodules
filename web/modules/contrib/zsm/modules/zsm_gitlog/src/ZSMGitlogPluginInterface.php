<?php

namespace Drupal\zsm_gitlog;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Contact entity.
 * @ingroup zsm_gitlog
 */
interface ZSMGitlogPluginInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>