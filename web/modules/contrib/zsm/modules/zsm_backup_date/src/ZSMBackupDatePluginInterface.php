<?php

namespace Drupal\zsm_backup_date;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Contact entity.
 * @ingroup zsm_backup_date
 */
interface ZSMBackupDatePluginInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>