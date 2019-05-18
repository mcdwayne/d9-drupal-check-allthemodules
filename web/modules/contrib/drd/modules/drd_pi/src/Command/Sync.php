<?php

namespace Drupal\drd_pi\Command;

use Drupal\drd\Command\BaseSystem;

/**
 * Class Sync.
 *
 * @package Drupal\drd
 */
class Sync extends BaseSystem {

  /**
   * Constructs a Sync command object.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_pi_sync';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:pi:sync')
      ->setDescription($this->trans('commands.drd_pi.action.sync.description'));
  }

}
