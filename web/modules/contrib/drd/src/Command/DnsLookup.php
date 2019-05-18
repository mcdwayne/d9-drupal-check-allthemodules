<?php

namespace Drupal\drd\Command;

/**
 * Class DnsLookup.
 *
 * @package Drupal\drd
 */
class DnsLookup extends BaseHost {

  /**
   * Construct the DnsLookup command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_dnslookup';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:dnslookup')
      ->setDescription($this->trans('commands.drd.action.dnslookup.description'));
  }

}
