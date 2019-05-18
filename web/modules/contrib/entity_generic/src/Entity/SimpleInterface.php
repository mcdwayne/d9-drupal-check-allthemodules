<?php

namespace Drupal\entity_generic\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines an interface for simple entities.
 */
interface SimpleInterface extends BasicInterface, EntityLabelInterface, EntityStatusInterface, EntityOwnerInterface, RevisionLogInterface {

}
