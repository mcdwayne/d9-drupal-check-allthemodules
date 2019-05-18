<?php

namespace Drupal\linky;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Defines a class for entity view builder for linky entities.
 */
class LinkyEntityViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function addContextualLinks(array &$build, EntityInterface $entity) {
    // No-op.
  }

}
