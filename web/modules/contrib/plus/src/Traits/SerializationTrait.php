<?php

namespace Drupal\plus\Traits;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Trait SerializationTrait.
 */
trait SerializationTrait {

  use DependencySerializationTrait {
    __sleep as traitSleep;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // Serialize dependencies.
    $this->traitSleep();

    $serialize = $this->serializeProperties();

    // Always serialize service identifiers from DependencySerializationTrait.
    $serialize[] = '_serviceIds';

    $properties = [];
    foreach (array_keys(get_object_vars($this)) as $property) {
      // Check to see if property can be serialized.
      if (in_array($property, $serialize) && $property !== 'sleepWakeupKeepProperties') {
        $properties[] = $property;
      }
      // Otherwise, unset the property for good measure.
      else {
        unset($this->$property);
      }
    }

    return $properties;
  }

  /**
   * Retrieves the property names that should be serialized.
   *
   * @return array
   *   An indexed array of property names to keep.
   */
  public function serializeProperties() {
    return [];
  }

}
