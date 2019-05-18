<?php

namespace Drupal\drd\Command;

/**
 * Class FlushCache.
 *
 * @package Drupal\drd
 */
class FlushCache extends BaseDomain {

  /**
   * Construct the FlushCache command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_flush_cache';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:flushcache')
      ->setDescription($this->trans('commands.drd.action.flushcache.description'));
  }

}
