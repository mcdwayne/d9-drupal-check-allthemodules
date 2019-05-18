<?php

namespace Drupal\entity_counter;

/**
 * Enumerates the cardinality values of the entity counter source plugins.
 */
final class EntityCounterSourceCardinality extends AbstractEnum {

  /**
   * Value indicating unlimited plugin instances are permitted.
   */
  const UNLIMITED = -1;

  /**
   * Value indicating a single plugin instance is permitted.
   */
  const SINGLE = 1;

}
