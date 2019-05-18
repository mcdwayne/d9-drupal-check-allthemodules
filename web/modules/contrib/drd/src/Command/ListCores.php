<?php

namespace Drupal\drd\Command;

/**
 * Class ListCores.
 *
 * @package Drupal\drd
 */
class ListCores extends ListEntities {

  /**
   * Construct the List command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_list_cores';
    $this->tableHeader = [
      $this->trans('cid'),
      $this->trans('name'),
      $this->trans('hid'),
      $this->trans('host'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->setName('drd:list:cores');
  }

}
