<?php

namespace Drupal\drd\Command;

use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class DomainMove.
 *
 * @package Drupal\drd
 */
class DomainMove extends BaseDomain {

  /**
   * Construct the DomainMove command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_domainmove';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:domainmove')
      ->setDescription($this->trans('commands.drd.action.domainmove.description'))
      ->addArgument(
        'dest-core-id',
        InputArgument::REQUIRED,
        $this->trans('commands.drd.action.domaimove.arguments.dest-core-id')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function setActionArguments(ActionBaseInterface $action, InputInterface $input) {
    parent::setActionArguments($action, $input);
    $action->setActionArgument('dest-core-id', $input->getArgument('dest-core-id'));
  }

}
