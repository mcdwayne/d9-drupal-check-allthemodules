<?php

namespace Drupal\mailjet_campaign;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Signupform entity.
 *
 */
interface CampaignInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
