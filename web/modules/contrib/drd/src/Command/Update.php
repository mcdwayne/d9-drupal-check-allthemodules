<?php

namespace Drupal\drd\Command;

/**
 * Class Update.
 *
 * @package Drupal\drd
 */
class Update extends BaseDomain {

  /**
   * Construct the Update command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_update';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:update')
      ->setDescription($this->trans('commands.drd.action.update.description'));
  }

}
