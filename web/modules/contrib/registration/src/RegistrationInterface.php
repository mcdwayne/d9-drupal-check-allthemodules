<?php

/**
 * @file
 * Contains \Drupal\registration\RegistrationInterface.
 */

namespace Drupal\registration;

use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a node entity.
 */
interface RegistrationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

}
