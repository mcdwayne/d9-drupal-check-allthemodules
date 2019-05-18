<?php

namespace Drupal\drd\Command;

use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class Php.
 *
 * @package Drupal\drd
 */
class Php extends BaseDomain {

  /**
   * Construct the Php command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_php';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:php')
      ->setDescription($this->trans('commands.drd.action.php.description'))
      ->addArgument(
        'php',
        InputArgument::REQUIRED,
        $this->trans('commands.drd.action.php.arguments.php')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function setActionArguments(ActionBaseInterface $action, InputInterface $input) {
    parent::setActionArguments($action, $input);
    $action->setActionArgument('php', $input->getArgument('php'));
  }

}
