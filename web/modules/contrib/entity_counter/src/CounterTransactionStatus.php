<?php

namespace Drupal\entity_counter;

/**
 * Enumerates the status values of the entity counter transaction entity.
 */
final class CounterTransactionStatus extends AbstractEnum {

  /**
   * Denotes that the entity counter transaction is queued.
   */
  const QUEUED = 0;

  /**
   * Denotes that the entity counter transaction is recorded.
   */
  const RECORDED = 1;

  /**
   * Denotes that the entity counter is closed or its value is upper limit.
   */
  const EXCEEDED_LIMIT = 2;

}
