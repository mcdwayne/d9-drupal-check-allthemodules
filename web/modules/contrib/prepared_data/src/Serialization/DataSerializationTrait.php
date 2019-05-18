<?php

namespace Drupal\prepared_data\Serialization;

use Drupal\prepared_data\PreparedData;

/**
 * Trait for handling serialization inside PreparedData objects.
 */
trait DataSerializationTrait {

  /**
   * The encoded string representation of the prepared data.
   *
   * @var string
   */
  protected $encoded;

  /**
   * The prepared data serialization service.
   *
   * @var \Drupal\prepared_data\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * Encodes the prepared data values to a string.
   *
   * Note that the encoding only handles data values.
   * The returned string neither contains any generated
   * meta information, expiry nor any key identifier.
   *
   * @param string|string[] $subset_keys
   *   When given, only the subset will be encoded and returned.
   *   Have a look at ::get() for an explanation about subset keys.
   *
   * @return string|NULL
   *   The encoding result as string.
   */
  public function encode($subset_keys = []) {
    if (empty($subset_keys)) {
      if (isset($this->encoded)) {
        // The already known encoded representation would be only initially
        // available, when the data has not been used in any other form before.
        return $this->encoded;
      }
      else {
        return $this->getSerializer()->encode($this);
      }
    }
    else {
      return (new PreparedData($this->get($subset_keys)))->encode();
    }
  }

  /**
   * Get the service for serializing prepared data.
   *
   * @return \Drupal\prepared_data\Serialization\SerializationInterface
   *   The serialization service.
   */
  public function getSerializer() {
    if (!isset($this->serializer)) {
      $this->setSerializer(\Drupal::service('prepared_data.serializer'));
    }
    return $this->serializer;
  }

  /**
   * Set the service for serializing prepared data.
   *
   * @param \Drupal\prepared_data\Serialization\SerializationInterface $serializer
   *   The serialization service.
   */
  public function setSerializer(SerializationInterface $serializer) {
    $this->serializer = $serializer;
  }

}
