<?php

namespace Drupal\drd\Command;

/**
 * Class DomainsEnableAll.
 *
 * @package Drupal\drd
 */
class DomainsEnableAll extends BaseCore {

  /**
   * Construct the DomainEnableAll command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_domains_enableall';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:domains:enableall')
      ->setDescription($this->trans('commands.drd.action.domains.enableall.description'));
  }

}
