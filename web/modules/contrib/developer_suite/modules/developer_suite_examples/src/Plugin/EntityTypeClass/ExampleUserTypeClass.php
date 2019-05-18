<?php

namespace Drupal\developer_suite_examples\Plugin\EntityTypeClass;

use Drupal\developer_suite\Storage\UserStorage;
use Drupal\developer_suite_examples\Entity\ExampleUser;

/**
 * Class ExampleUserTypeClass.
 *
 * @package Drupal\developer_suite_examples\EntityStorage
 *
 * @EntityTypeClass(
 *   id = "user_type_class",
 *   entity = "user",
 *   label = @Translation("User type class"),
 * )
 */
class ExampleUserTypeClass extends UserStorage {

  /**
   * Returns the entity class.
   *
   * @return mixed
   *   The entity class.
   */
  public function getEntityTypeClass() {
    return ExampleUser::class;
  }

}
