<?php

namespace Drupal\parameter_message;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Message entity.
 */
interface ParameterMessageInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
