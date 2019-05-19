<?php

namespace Drupal\zsm_mail_alert;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Contact entity.
 * @ingroup zsm_mail_alert
 */
interface ZSMMailAlertPluginInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>