<?php

namespace Drupal\people;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for peoples.
 */
class PeopleStorage extends SqlContentEntityStorage implements PeopleStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getCompany(PeopleInterface $people) {
    if ($organization = $people->organization->entity) {
      if ($organization->bundle() == 'company') {
        return $organization;
      }

      while ($organization = $organization->get('parent')->entity) {
        if ($organization->bundle() == 'company') {
          return $organization;
        }
      }
    }
  }

}
