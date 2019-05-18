<?php

namespace Drupal\drd\Command;

/**
 * Class Cron.
 *
 * @package Drupal\drd
 */
class Cron extends BaseDomain {

  /**
   * Construct the Cron command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_cron';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:cron')
      ->setDescription($this->trans('commands.drd.action.cron.description'));
  }

}
