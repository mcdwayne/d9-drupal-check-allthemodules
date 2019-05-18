<?php

namespace Drupal\cbo_organization;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a organization entity.
 */
interface OrganizationInterface extends ContentEntityInterface {

  /**
   * Get the parent organization.
   *
   * @return \Drupal\cbo_organization\OrganizationInterface|null
   *   The parent organization.
   */
  public function getParent();

}
