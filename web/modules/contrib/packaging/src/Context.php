<?php

/**
 * @file
 * Contains \Drupal\packaging\Context.
 */

namespace Drupal\packaging;


/**
 * Contains context information needed for packaging strategies.
 */
class Context {

  /** Constant used in internal implementation to indicate unlimited weight */
  const UNLIMITED_PACKAGE_WEIGHT = -1;

  /** Constant used in internal implementation to indicate unlimited volume */
  const UNLIMITED_PACKAGE_VOLUME = -1;

  protected $strategy = NULL;

  protected $maximum_weight = self::UNLIMITED_PACKAGE_WEIGHT;
  protected $maximum_volume = self::UNLIMITED_PACKAGE_VOLUME;
  protected $length_units = 'IN';
  protected $weight_units = 'LB';
  protected $weight_markup_function = 'packaging_weight_markup';

  protected static $keys = array();


  /**
   * Invokes packaging strategy chosen for this context..
   *
   * @param $products
   *   An array of nodes of type Product.
   *
   * @return
   *   An array of Package objects, each containing one or more of the products.
   */
  public function packageProducts(array $products) {
    return $this->getStrategy()->packageProducts($this, $products);
  }


  /**
   * Mutator for strategy property.
   */
  public function setStrategy(Strategy $strategy) {
    $this->strategy = $strategy;
    return $this;
  }

  /**
   * Accessor for strategy property.
   */
  public function getStrategy() {
    return $this->strategy;
  }

  /**
   * Mutator for maximum_weight property.
   */
  public function setMaximumPackageWeight($maximum_weight = self::UNLIMITED_PACKAGE_WEIGHT) {
    $this->maximum_weight = $maximum_weight;
    return $this;
  }

  /**
   * Accessor for maximum_weight property.
   */
  public function getMaximumPackageWeight() {
    return $this->maximum_weight;
  }

  /**
   * Compares weight against maximum allowed weight.
   *
   * @param $weight
   *   Float containing weight in default weight units.
   *
   * @return
   *   TRUE if $weight exceeds maximum allowed weight, FALSE otherwise.
   */
  public function exceedsMaximumPackageWeight($weight) {
    if ($this->maximum_weight != self::UNLIMITED_PACKAGE_WEIGHT &&
        $weight > $this->maximum_weight) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Compares weight against maximum allowed weight.
   *
   * @param $weight
   *   Float containing weight in default weight units.
   *
   * @return
   *   TRUE if $weight is less than the maximum allowed weight, FALSE otherwise.
   */
  public function lessThanMaximumPackageWeight($weight) {
    if ($this->maximum_weight == self::UNLIMITED_PACKAGE_WEIGHT ||
        $weight < $this->maximum_weight) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Mutator for weight_units property.
   */
  public function setDefaultWeightUnits($weight_units = 'LB') {
    $this->weight_units = $weight_units;
    // @todo: Need to modify $maximum_weight to agree with the new units.
    return $this;
  }

  /**
   * Accessor for weight_units property.
   */
  public function getDefaultWeightUnits() {
    return $this->weight_units;
  }

  /**
   * A function may be specified to modify the package weight after packaging
   * is completed. This is intended to compensate for the weight of packaging
   * materials.
   */
  public function setWeightMarkupFunction($weight_markup_function) {
    $this->weight_markup_function = $weight_markup_function;
    return $this;
  }

  /**
   * Mutator for maximum_volume property.
   */
  public function setMaximumPackageVolume($maximum_volume = self::UNLIMITED_PACKAGE_VOLUME) {
    $this->maximum_volume = $maximum_volume;
    return $this;
  }

  /**
   * Accessor for maximum_volume property.
   */
  public function getMaximumPackageVolume() {
    return $this->maximum_volume;
  }

  /**
   * Compares volume against maximum allowed volume.
   *
   * @param $volume
   *   Float containing volume in default length units.
   *
   * @return
   *   TRUE if $volume exceeds maximum allowed volume, FALSE otherwise.
   */
  public function exceedsMaximumPackageVolume($volume) {
    if ($this->maximum_volume != self::UNLIMITED_PACKAGE_VOLUME &&
        $volume > $this->maximum_volume) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Compares volume against maximum allowed volume.
   *
   * @param $volume
   *   Float containing volume in default length units.
   *
   * @return
   *   TRUE if $volume is less than the maximum allowed volume, FALSE otherwise.
   */
  public function lessThanMaximumPackageVolume($volume) {
    if ($this->maximum_volume == self::UNLIMITED_PACKAGE_VOLUME ||
        $volume < $this->maximum_volume) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Mutator for length_units property.
   */
  public function setDefaultLengthUnits($length_units = 'LB') {
    $this->length_units = $length_units;
    // @todo: Need to modify $maximum_volume to agree with the new units.
    return $this;
  }

  /**
   * Accessor for length_units property.
   */
  public function getDefaultLengthUnits() {
    return $this->length_units;
  }

  /**
   * Designates product properties as "keys" for packaging purposes.
   *
   * Keys may be any type of public property attached to the product object.
   * However, if the property is an object type, it MUST be castable to a
   * string. This typically means the object type must implement the magic
   * method __toString().
   *
   * @param $keys
   *   Associative array with values equal to names of properties.
   */
  public static function designateKeys(array $keys = array()) {
    self::$keys = $keys;
  }

  /**
   * Accessor for keys property.
   */
  public static function getKeys() {
    return self::$keys;
  }

}
