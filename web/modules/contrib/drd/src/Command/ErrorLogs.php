<?php

namespace Drupal\drd\Command;

/**
 * Class ErrorLogs.
 *
 * @package Drupal\drd
 */
class ErrorLogs extends BaseDomain {

  /**
   * Construct the ErrorLogs command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_error_logs';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:errorlogs')
      ->setDescription($this->trans('commands.drd.action.errorlogs.description'));
  }

}
