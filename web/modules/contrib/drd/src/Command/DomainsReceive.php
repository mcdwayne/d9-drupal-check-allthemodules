<?php

namespace Drupal\drd\Command;

/**
 * Class DomainsReceive.
 *
 * @package Drupal\drd
 */
class DomainsReceive extends BaseCore {

  /**
   * Construct the DomainReceive command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_domains_receive';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:domains:receive')
      ->setDescription($this->trans('commands.drd.action.domains.receive.description'));
  }

}
