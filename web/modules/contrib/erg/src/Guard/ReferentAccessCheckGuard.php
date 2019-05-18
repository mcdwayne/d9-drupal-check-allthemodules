<?php

declare(strict_types = 1);

namespace Drupal\erg\Guard;

use Drupal\erg\EntityReference;
use Drupal\Core\Entity\EntityInterface;

/**
 * Guards references based on referent access.
 */
final class ReferentAccessCheckGuard extends EntityAccessCheckGuardBase {

  /**
   * {@inheritdoc}
   */
  protected function getAccessTarget(
    EntityReference $entityReference
  ): ?EntityInterface {
    return $entityReference->getReferent();
  }

}
