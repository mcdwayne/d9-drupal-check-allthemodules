<?php

namespace Drupal\drd\Command;

/**
 * Class Database.
 *
 * @package Drupal\drd
 */
class Database extends BaseDomain {

  /**
   * Construct the Database command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_database';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:database')
      ->setDescription($this->trans('commands.drd.action.blocks.description'));
  }

}
