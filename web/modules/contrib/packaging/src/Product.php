<?php

/**
 * @file
 * Contains \Drupal\packaging\Product.
 */

namespace Drupal\packaging;


/**
 * Represents a physical product that may be put into packages.
 */
class Product {

  protected $origin;
  protected $destination;

  protected $quantity     = 0.0;
  protected $pkg_qty      = 0;

  protected $price        = 0.0;
  protected $weight       = 0.0;
  protected $weight_units = 'LB';

  protected $length       = 0.0;
  protected $width        = 0.0;
  protected $height       = 0.0;
  protected $volume       = 0.0;
  protected $length_units = 'IN';

  /**
   * Harmonized Commodity Description and Coding System (HS) code.
   */
  protected $HS_code = '';


  /**
   * Constructs a new Product object given a Drupal Commerce Product
   * entity.
   */
  public static function constructFromCommerceProduct($product) {

    $construct = new Product();

    return $construct;
  }

  /**
   * Constructs a new Product object given an array of Drupal Commerce
   * Product entities.
   */
  public static function constructFromCommerceProductMultiple(array $products) {
    $constructs = array();

    foreach ($products as $product) {
      $constructs[] = self::constructFromCommerceProduct($product);
    }

    return $constructs;
  }


  /**
   * Constructs a new Product object given an Ubercart Product object.
   */
  public static function constructFromUbercartProduct($product) {

    // New instance.
    $construct = new Product();

    // Copy properties into this instance.
    $construct->quantity     = $product->qty;
    $construct->pkg_qty      = $product->pkg_qty;

    $construct->price        = $product->sell_price;
    $construct->weight       = $product->weight;
    $construct->weight_units = $product->weight_units;

    $construct->length       = $product->length;
    $construct->width        = $product->width;
    $construct->height       = $product->height;
    $construct->volume       = $product->length * $product->width * $product->height;
    $construct->length_units = $product->length_units;

    // Copy any designated properties from the Ubercart product type to the
    // Product type.
    foreach (Context::getKeys() as $key) {
      if (property_exists($product, $key)) {
        $construct->$key = $product->$key;
      }
    }

    return $construct;
  }

  /**
   * Constructs a new Product object given an array of Ubercart Product objects.
   */
  public static function constructFromUbercartProductMultiple(array $products) {
    $constructs = array();

    foreach ($products as $product) {
      $constructs[] = self::constructFromUbercartProduct($product);
    }

    return $constructs;
  }

  /**
   * Constructor.
   *
   * WARNING. If you explicitly set the property values through the constructor
   * rather than the mutator functions, you may be introducting inconsistencies.
   * For example, the mutators ensure that volume always equals length x width
   * x height, as expressed in the length_units of this instance.
   *
   * @param $property_values
   *   Optional. An associative array with property names as keys and initial
   *   values as values.
   */
  public function __construct(array $property_values = array()) {
    foreach ($property_values as $property => $value) {
      if (property_exists($this, $property)) {
        $this->$property = $value;
      }
    }
  }

  /**
   * Accessor for quantity property.
   */
  public function getQuantity() {
    return $this->quantity;
  }

  /**
   * Mutator for quantity property.
   */
  public function setQuantity($quantity) {
    $this->quantity = $quantity;
    return $this;
  }

  /**
   * Accessor for pkg_qty property.
   */
  public function getPackageQuantity() {
    return $this->pkg_qty;
  }

  /**
   * Mutator for pkg_qty property.
   */
  public function setPackageQuantity($pkg_qty) {
    $this->quantity = $pkg_qty;
    return $this;
  }

  /**
   * Accessor for price property.
   */
  public function getPrice() {
    return $this->price;
  }

  /**
   * Mutator for price property.
   */
  public function setPrice($price) {
    $this->price = $price;
    return $this;
  }

  /**
   * Accessor for weight property.
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * Mutator for weight property.
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * Accessor for weight_units property.
   */
  public function getWeightUnits() {
    return $this->weight_units;
  }

  /**
   * Mutator for weight_units property.
   */
  public function setWeightUnits($weight_units) {
    $this->weight_units = $weight_units;
    return $this;
  }

  /**
   * Accessor for dimensions (length, width, and height properties).
   */
  public function getDimensions() {
    return array(
      'length' => $this->length,
      'width'  => $this->width,
      'height' => $this->height
    );
  }

  /**
   * Mutator for dimensions (length, width, and height properties).
   *
   * @param $dimensions
   *   Associative array with keys 'length', 'width', and 'height'.
   */
  public function setDimensions(array $dimensions = array()) {
    // Use current values of length, width, and height if not specified.
    $dimensions  += $this->getDimensions();
    $this->length = $dimensions['length'];
    $this->width  = $dimensions['width'];
    $this->height = $dimensions['height'];

    // Re-compute volume
    $this->volume = $this->length * $this->width * $this->height;
    return $this;
  }

  /**
   * Accessor for volume property.
   */
  public function getVolume() {
    return $this->volume;
  }

  /**
   * The mutator for the weight property is deliberately non-public. If you
   * want to alter the volume you should use setDimensions() instead.
   */
  public function setVolume($volume) {
    return $this->volume = $volume;
    return $this;
  }

  /**
   * Accessor for length_units property.
   */
  public function getLengthUnits() {
    return $this->length_units;
  }

  /**
   * Mutator for length_units property.
   *
   * Note that length, width, height, and volume properties will be
   * automatically re-computed so as to keep them expressed in terms of
   * length_units.  Because of this, you should ensure that you set the
   * length units of the product first, before you set the dimensions.
   */
  public function setLengthUnits($length_units) {
    $this->length_units = $length_units;
    // Re-compute length, width and height with new units.
    // $this->length = packaging_length_conversion($this->length, $length_units);
    // $this->width = packaging_length_conversion($this->width, $length_units);
    // $this->height = packaging_length_conversion($this->height, $length_units);
    // Re-compute volume with new units. Volume is always expressed in terms of
    // the cube of the product length units.
    $this->volume = $this->length * $this->width * $this->height;
    return $this;
  }

}
