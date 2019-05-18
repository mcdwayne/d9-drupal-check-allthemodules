<?php


namespace Drupal\flags\Mapping;

/**
 * Interface for flag mapping service to map different values to flag codes.
 *
 * Interface FlagMappingInterface
 */
interface FlagMappingInterface {

  /**
   * Maps provided string to a flag code.
   * Returned string should be lower case flag code.
   *
   * @param string $value   Value of the source data.
   *
   * @return string
   */
  public function map($value);

  /**
   * Gets array with attributes for each option element.
   *
   * @param array $options
   *   The array of keys, either language codes, or country codes.
   *
   * @return \Drupal\Core\Template\Attribute[]
   *   List of classes per option.
   */
  public function getOptionAttributes(array $options = []);

  /**
   * Returns array of extra classes for specific mapper.
   *
   * @return string[]
   */
  public function getExtraClasses();

}
