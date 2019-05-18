<?php

namespace Drupal\cbo_organization\Tests;

use Drupal\cbo_organization\Entity\Organization;

/**
 * Helper for organization tests.
 */
trait OrganizationTrait {

  /**
   * Creates a organization based on default settings.
   */
  protected function createOrganization(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'type' => 'company',
      'title' => $this->randomMachineName(8),
      'number' => $this->randomMachineName(8),
    ];
    $entity = Organization::create($settings);
    $entity->save();

    return $entity;
  }

}
