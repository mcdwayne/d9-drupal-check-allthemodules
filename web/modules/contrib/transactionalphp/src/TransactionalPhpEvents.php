<?php

namespace Drupal\transactionalphp;

/**
 * Contains all events thrown by Transactional PHP.
 */
final class TransactionalPhpEvents {

  /**
   * The name of the event triggered just before a transaction is committed.
   *
   * This event allows modules to react to a transaction being committed. The
   * event listener method receives a
   * \Drupal\transactionalphp\TransactionalPhpEvent instance.
   *
   * @Event
   *
   * @see \Drupal\transactionalphp\TransactionalPhpEvent
   *
   * @var string
   */
  const PRE_COMMIT = 'transactionalphp.pre_commit';

}
