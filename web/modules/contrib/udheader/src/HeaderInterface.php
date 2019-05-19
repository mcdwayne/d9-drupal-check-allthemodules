<?php

namespace Drupal\udheader;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides and interface defining an Ubuntu Drupal Header entity.
 * @ingroup udheader
 */
interface HeaderInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

}
