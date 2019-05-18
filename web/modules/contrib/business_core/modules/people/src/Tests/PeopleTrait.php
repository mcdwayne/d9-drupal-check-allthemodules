<?php

namespace Drupal\people\Tests;

use Drupal\people\Entity\People;

/**
 * Helper for people tests.
 */
trait PeopleTrait {

  /**
   * Creates a people based on default settings.
   */
  protected function createPeople(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'type' => 'employee',
      'title' => $this->randomMachineName(8),
      'number' => $this->randomMachineName(8),
      'organization' => $this->organization->id(),
    ];
    $entity = People::create($settings);
    $entity->save();

    return $entity;
  }

}
