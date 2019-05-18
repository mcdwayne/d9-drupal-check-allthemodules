<?php

namespace Drupal\drd\Command;

/**
 * Class ListHosts.
 *
 * @package Drupal\drd
 */
class ListHosts extends ListEntities {

  /**
   * Construct the List command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_list_hosts';
    $this->tableHeader = [
      $this->trans('hid'),
      $this->trans('name'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->setName('drd:list:hosts');
  }

}
