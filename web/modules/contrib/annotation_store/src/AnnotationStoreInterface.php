<?php

namespace Drupal\annotation_store;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a AnnotationStore entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup annotation_store
 */
interface AnnotationStoreInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
