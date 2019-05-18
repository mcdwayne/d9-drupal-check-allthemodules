<?php
/**
 * @file
 * Contains \Drupal\impression\BaseInterface.
 */

namespace Drupal\impression;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Base entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup impression
 */
interface BaseInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
