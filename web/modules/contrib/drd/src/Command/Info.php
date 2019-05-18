<?php

namespace Drupal\drd\Command;

/**
 * Class Info.
 *
 * @package Drupal\drd
 */
class Info extends BaseDomain {

  /**
   * Construct the Info command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_info';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:info')
      ->setDescription($this->trans('commands.drd.action.info.description'));
  }

}
