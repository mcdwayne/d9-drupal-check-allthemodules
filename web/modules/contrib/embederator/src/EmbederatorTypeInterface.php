<?php

namespace Drupal\embederator;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Provides an interface for defining Embederator type entities.
 */
interface EmbederatorTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface {}
