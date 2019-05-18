<?php

/**
 * @file
 * Contains \Drupal\cronpub\CronpubEntityInterface.
 */

namespace Drupal\cronpub;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Cronpub entity entities.
 *
 * @ingroup cronpub
 */
interface CronpubEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

}
