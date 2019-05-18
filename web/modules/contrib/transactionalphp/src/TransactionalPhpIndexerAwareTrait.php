<?php

namespace Drupal\transactionalphp;

use \Gielfeldt\TransactionalPHP\Indexer;

/**
 * TransactionalPhpIndexerAware trait.
 *
 * @package Drupal\transactionalphp
 *
 * @author Thomas Gielfeldt <thomas@gielfeldt.dk>
 */
trait TransactionalPhpIndexerAwareTrait {
  /**
   * The transactional php indexer.
   *
   * @var \Gielfeldt\TransactionalPHP\Indexer
   */
  protected $transactionalPhpIndexer = NULL;

  /**
   * Sets the transactional php indexer.
   *
   * @param \Gielfeldt\TransactionalPHP\Indexer|NULL $transactional_php_indexer
   *   An Indexer instance or NULL.
   */
  public function setTransactionalPhpIndexer(Indexer $transactional_php_indexer = NULL) {
    $this->transactionalPhpIndexer = $transactional_php_indexer;
  }

  /**
   * Get the transactional php indexer.
   *
   * @return \Gielfeldt\TransactionalPHP\Indexer|NULL
   *   The transactional php indexer instance or NULL.
   */
  public function getTransactionalPhpIndexer() {
    return $this->transactionalPhpIndexer;
  }

}
