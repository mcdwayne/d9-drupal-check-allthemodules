<?php

namespace Drupal\embederator;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a embederator entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup embederator
 */
interface EmbederatorInterface extends ContentEntityInterface, EntityOwnerInterface {}
