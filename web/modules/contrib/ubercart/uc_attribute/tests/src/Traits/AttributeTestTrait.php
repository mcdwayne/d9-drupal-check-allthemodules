<?php

namespace Drupal\Tests\uc_attribute\Traits;

/**
 * Utility functions to provide products for test purposes.
 *
 * This trait can only be used in classes which already use
 * RandomGeneratorTrait. RandomGeneratorTrait is used in all
 * the PHPUnit and Simpletest base classes.
 */
trait AttributeTestTrait {

  /**
   * Creates an attribute.
   *
   * @param array $data
   *   (optional) An associative array of attribute initialization data.
   * @param bool $save
   *   If TRUE, save attribute in database.
   *
   * @return array
   *   Associative array of attribute data.
   */
  protected function createAttribute(array $data = [], $save = TRUE) {
    $attribute = $data + [
      'name' => $this->randomMachineName(8),
      'label' => $this->randomMachineName(8),
      'description' => $this->randomMachineName(8),
      'required' => mt_rand(0, 1) ? TRUE : FALSE,
      'display' => mt_rand(0, 3),
      'ordering' => mt_rand(-10, 10),
    ];
    $attribute = (object) $attribute;

    if ($save) {
      uc_attribute_save($attribute);
    }
    return $attribute;
  }

  /**
   * Creates an attribute option.
   *
   * @param array $data
   *   Array containing attribute data, with keys corresponding to the
   *   columns of the {uc_attribute} table.
   * @param bool $save
   *   If TRUE, save attribute option in database.
   *
   * @return array
   *   Associative array of attribute option data.
   */
  protected function createAttributeOption(array $data = [], $save = TRUE) {
    $max_aid = db_select('uc_attributes', 'a')
      ->fields('a', ['aid'])
      ->orderBy('aid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    $option = $data + [
      'aid' => $max_aid,
      'name' => $this->randomMachineName(8),
      'cost' => mt_rand(-500, 500),
      'price' => mt_rand(-500, 500),
      'weight' => mt_rand(-500, 500),
      'ordering' => mt_rand(-10, 10),
    ];
    $option = (object) $option;

    if ($save) {
      uc_attribute_option_save($option);
    }
    return $option;
  }

}
