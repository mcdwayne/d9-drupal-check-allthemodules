<?php

namespace Drupal\drd\Command;

use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class Ping.
 *
 * @package Drupal\drd
 */
class Ping extends BaseDomain {

  /**
   * Construct the Ping command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_ping';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:ping')
      ->setDescription($this->trans('commands.drd.action.ping.description'))
      ->addOption(
        'save',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.action.ping.arguments.save')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function setActionArguments(ActionBaseInterface $action, InputInterface $input) {
    parent::setActionArguments($action, $input);
    if ($save = $input->getOption('save')) {
      $action->setActionArgument('save', TRUE);
    }
    else {
      $action->setActionArgument('save', FALSE);
    }
  }

}
