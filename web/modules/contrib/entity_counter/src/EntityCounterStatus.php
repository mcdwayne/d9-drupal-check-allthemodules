<?php

namespace Drupal\entity_counter;

/**
 * Enumerates the status values of the entity counter entity.
 */
final class EntityCounterStatus extends AbstractEnum {

  /**
   * Entity counter status open.
   */
  const OPEN = 'open';

  /**
   * Entity counter status closed.
   */
  const CLOSED = 'closed';

  /**
   * Entity counter status maximum upper limit.
   */
  const MAX_UPPER_LIMIT = 'max_upper_limit';

}
