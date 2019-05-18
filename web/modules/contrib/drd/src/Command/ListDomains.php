<?php

namespace Drupal\drd\Command;

/**
 * Class ListDomains.
 *
 * @package Drupal\drd
 */
class ListDomains extends ListEntities {

  /**
   * Construct the List command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_list_domains';
    $this->tableHeader = [
      $this->trans('did'),
      $this->trans('name'),
      $this->trans('domain'),
      $this->trans('cid'),
      $this->trans('core'),
      $this->trans('hid'),
      $this->trans('host'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->setName('drd:list:domains');
  }

}
