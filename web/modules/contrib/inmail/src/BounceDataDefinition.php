<?php

namespace Drupal\inmail;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Contains bounce information from the analyzer.
 *
 * The setter methods only have effect the first time they are called, so values
 * are only writable once.
 *
 * @ingroup analyzer
 */
class BounceDataDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return [
      'status_code' => DataDefinition::create('string'),
      'recipient' => DataDefinition::create('string'),
      'reason' => DataDefinition::create('string'),
    ];
  }
}
