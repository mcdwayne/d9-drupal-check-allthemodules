<?php

namespace Drupal\entity_counter;

/**
 * Enumerates the value types of the entity counter source plugins.
 */
final class EntityCounterSourceValue extends AbstractEnum {

  /**
   * Value indicating if the plugin obtains the absolute value to add.
   */
  const ABSOLUTE = 'absolute';

  /**
   * Value indicating if the plugin obtains and incremental value to add.
   */
  const INCREMENTAL = 'incremental';

}
