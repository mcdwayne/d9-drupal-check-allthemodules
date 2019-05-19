<?php
/**
 * @file
 * Representation of a stat row.
 *
 * @see \Drupal\migrate\Row
 */

namespace Drupal\stats;

use Drupal\Component\Utility\NestedArray;

/**
 * Stores a stat row.
 */
class Row {

  /**
   * The actual values of the source row.
   *
   * @var array
   */
  protected $source = [];

  /**
   * The destination values.
   *
   * @var array
   */
  protected $destination = [];

  /**
   * Level separator of destination and source properties.
   */
  const PROPERTY_SEPARATOR = '/';

  /**
   * Whether the source has been frozen already.
   *
   * Once frozen the source can not be changed any more.
   *
   * @var bool
   */
  protected $frozen = FALSE;

  /**
   * The raw destination properties.
   *
   * Unlike $destination which is set by using
   * \Drupal\Component\Utility\NestedArray::setValue() this array contains
   * the destination as setDestinationProperty was called.
   *
   * @var array
   *   The raw destination.
   *
   * @see getRawDestination()
   */
  protected $rawDestination = [];

  /**
   * The empty destination properties.
   *
   * @var array
   */
  protected $emptyDestinationProperties = [];

  /**
   * Constructs a \Drupal\stats\Row object.
   *
   * @param array $values
   *   An array of values to add as properties on the object.
   */
  public function __construct(array $values = []) {
    $this->source = $values;
  }

  /**
   * Determines whether a source has a property.
   *
   * @param string $property
   *   A property on the source.
   *
   * @return bool
   *   TRUE if the source has property; FALSE otherwise.
   */
  public function hasSourceProperty($property) {
    return NestedArray::keyExists($this->source, explode(static::PROPERTY_SEPARATOR, $property));
  }

  /**
   * Retrieves a source property.
   *
   * @param string $property
   *   A property on the source.
   *
   * @return mixed|null
   *   The found returned property or NULL if not found.
   */
  public function getSourceProperty($property) {
    return static::getNestedValue($this->source, $property);
  }

  /**
   * Returns the whole source array.
   *
   * @return array
   *   An array of source plugins.
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * Sets a source property.
   *
   * This can only be called from the source plugin.
   *
   * @param string $property
   *   A property on the source.
   * @param mixed $data
   *   The property value to set on the source.
   *
   * @throws \Exception
   */
  public function setSourceProperty($property, $data) {
    if ($this->frozen) {
      throw new \Exception("The source is frozen and can't be changed any more");
    }
    else {
      NestedArray::setValue($this->source, explode(static::PROPERTY_SEPARATOR, $property), $data, TRUE);
    }
  }

  /**
   * Freezes the source.
   *
   * @return $this
   */
  public function freezeSource() {
    $this->frozen = TRUE;
    return $this;
  }

  /**
   * Clones the row with an empty set of destination values.
   *
   * @return static
   */
  public function cloneWithoutDestination() {
    return (new static($this->getSource()))->freezeSource();
  }

  /**
   * Tests if destination property exists.
   *
   * @param array|string $property
   *   An array of properties on the destination.
   *
   * @return bool
   *   TRUE if the destination property exists.
   */
  public function hasDestinationProperty($property) {
    return NestedArray::keyExists($this->destination, explode(static::PROPERTY_SEPARATOR, $property));
  }

  /**
   * Sets destination properties.
   *
   * @param string $property
   *   The name of the destination property.
   * @param mixed $value
   *   The property value to set on the destination.
   */
  public function setDestinationProperty($property, $value) {
    $this->rawDestination[$property] = $value;
    NestedArray::setValue($this->destination, explode(static::PROPERTY_SEPARATOR, $property), $value, TRUE);
  }

  /**
   * Removes destination property.
   *
   * @param string $property
   *   The name of the destination property.
   */
  public function removeDestinationProperty($property) {
    unset($this->rawDestination[$property]);
    NestedArray::unsetValue($this->destination, explode(static::PROPERTY_SEPARATOR, $property));
  }

  /**
   * Sets a destination to be empty.
   *
   * @param string $property
   *   The destination property.
   */
  public function setEmptyDestinationProperty($property) {
    $this->emptyDestinationProperties[] = $property;
  }

  /**
   * Gets the empty destination properties.
   *
   * @return array
   *   An array of destination properties.
   */
  public function getEmptyDestinationProperties() {
    return $this->emptyDestinationProperties;
  }

  /**
   * Returns the whole destination array.
   *
   * @return array
   *   An array of destination values.
   */
  public function getDestination() {
    return $this->destination;
  }

  /**
   * Returns the raw destination. Rarely necessary.
   *
   * For example calling setDestination('foo/bar', 'baz') stats in
   * @code
   * $this->destination['foo']['bar'] = 'baz';
   * $this->rawDestination['foo/bar'] = 'baz';
   * @endcode
   *
   * @return array
   *   The raw destination values.
   */
  public function getRawDestination() {
    return $this->rawDestination;
  }

  /**
   * Returns the value of a destination property.
   *
   * @param string $property
   *   The name of a property on the destination.
   *
   * @return mixed
   *   The destination value.
   */
  public function getDestinationProperty($property) {
    return static::getNestedValue($this->destination, $property);
  }

  /**
   * Get property of row of either source or destination.
   *
   * @param string $property
   *   The name of the property to get the value from. If it starts with '@' it
   *   is meant to be the destination property, source otherwise. Might provide
   *   child properties seperated by '/' for getting a nested value.
   *
   * @return mixed|null
   */
  public function getProperty($property) {
    $p = $this->normalizePropertyString($property);
    if ($p['is_source']) {
      return $this->getSourceProperty($p['property']);
    }
    else {
      return $this->getDestinationProperty($p['property']);
    }
  }

  /**
   * Set property of row for either source or destination.
   *
   * @param string $property
   *   The name of the property to set the value to. If it starts with '@' it
   *   is meant to be the destination property, source otherwise. Might provide
   *   child properties seperated by '/' for setting a nested value.
   *
   * @param mixed $data
   *   Date to set.
   *
   * @return mixed|null
   */
  public function setProperty($property, $data) {
    $p = $this->normalizePropertyString($property);
    if ($p['is_source']) {
      return $this->setSourceProperty($p['property'], $data);
    }
    else {
      return $this->setDestinationProperty($p['property'], $data);
    }
  }

  /**
   * Static helper to get a value given by property string from a nested data.
   *
   * @param array $data
   * @param string $property
   *
   * @return mixed
   */
  public static function getNestedValue($data, $property) {
    $return = NestedArray::getValue($data, explode(static::PROPERTY_SEPARATOR, $property), $key_exists);
    if ($key_exists) {
      return $return;
    }
  }

  /**
   * Normalizes the given property string.
   *
   * @param string $property
   *
   * @return array
   *
   * @see \Drupal\migrate\Plugin\migrate\process\Get::transform()
   */
  protected function normalizePropertyString($property) {
    $is_source = TRUE;
    if ($property[0] == '@') {
      $property = preg_replace_callback('/^(@?)((?:@@)*)([^@]|$)/', function ($matches) use (&$is_source) {
        // If there are an odd number of @ in the beginning, it's a
        // destination.
        $is_source = empty($matches[1]);
        // Remove the possible escaping and do not lose the terminating
        // non-@ either.
        return str_replace('@@', '@', $matches[2]) . $matches[3];
      }, $property);
    }
    return [
      'is_source' => $is_source,
      'property' => $property,
    ];
  }

}

