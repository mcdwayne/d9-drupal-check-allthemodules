<?php

namespace Drupal\Tests\mass_contact\Kernel;

use Drupal\Component\Utility\Unicode;
use Drupal\mass_contact\Entity\MassContactCategory;

/**
 * Helper methods to create mass contact categories for testing.
 */
trait CategoryCreationTrait {

  /**
   * Creates a category.
   *
   * @return \Drupal\mass_contact\Entity\MassContactCategoryInterface
   *   The new category entity.
   */
  public function createCategory(array $settings = []) {
    $settings += [
      'id' => Unicode::strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'recipients' => [],
      'selected' => FALSE,
    ];
    $category = MassContactCategory::create($settings);
    $category->save();
    return $category;
  }

}
