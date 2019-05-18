<?php

namespace Drupal\entity_counter;

/**
 * Enumerates the operation types of the entity counter transaction entity.
 */
final class CounterTransactionOperation extends AbstractEnum {

  /**
   * Denotes that is a cancel entity counter transaction operation.
   */
  const CANCEL = 0;

  /**
   * Denotes that is an add entity counter transaction operation.
   */
  const ADD = 1;

}
