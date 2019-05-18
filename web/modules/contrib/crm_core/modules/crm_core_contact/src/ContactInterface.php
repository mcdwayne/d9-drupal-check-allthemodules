<?php

namespace Drupal\crm_core_contact;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines methods for CRM Contact entities.
 */
interface ContactInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

}
