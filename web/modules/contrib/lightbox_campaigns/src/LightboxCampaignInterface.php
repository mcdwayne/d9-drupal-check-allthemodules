<?php

namespace Drupal\lightbox_campaigns;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Lightbox Campaign entity.
 *
 * @ingroup lightbox_campaigns
 */
interface LightboxCampaignInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
