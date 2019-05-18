<?php

namespace Drupal\transactionalphp;

/**
 * TransactionalPhpAware trait.
 *
 * @package Drupal\transactionalphp
 *
 * @author Thomas Gielfeldt <thomas@gielfeldt.dk>
 */
trait TransactionalPhpAwareTrait {
  /**
   * The transactional php connection.
   *
   * @var \Drupal\transactionalphp\TransactionalPhp
   */
  protected $transactionalPhp = NULL;

  /**
   * Sets the transactional php.
   *
   * @param TransactionalPhp|NULL $transactional_php
   *   A TransactionalPhp instance or NULL.
   */
  public function setTransactionalPhp(TransactionalPhp $transactional_php = NULL) {
    $this->transactionalPhp = $transactional_php;
  }

  /**
   * Get the transactional php.
   *
   * @return TransactionalPhp|NULL $transactional_php
   *   The transactional php instance or NULL.
   */
  public function getTransactionalPhp() {
    return $this->transactionalPhp;
  }

}
