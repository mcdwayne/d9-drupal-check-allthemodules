<?php

namespace Drupal\cbo_resource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a resource entity.
 */
interface ResourceInterface extends ContentEntityInterface, EntityChangedInterface {

}
