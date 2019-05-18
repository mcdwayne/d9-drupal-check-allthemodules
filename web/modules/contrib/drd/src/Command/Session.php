<?php

namespace Drupal\drd\Command;

/**
 * Class Session.
 *
 * @package Drupal\drd
 */
class Session extends BaseDomain {

  /**
   * Construct the Session command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_session';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:session')
      ->setDescription($this->trans('commands.drd.action.session.description'));
  }

}
