<?php

namespace Drupal\drd\Command;

use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class Blocks.
 *
 * @package Drupal\drd
 */
class Blocks extends BaseDomain {

  /**
   * Constructu the Block commands.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_blocks';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:blocks')
      ->setDescription($this->trans('commands.drd.action.blocks.description'))
      ->addOption(
        'module',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.action.blocks.arguments.module')
      )
      ->addOption(
        'delta',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.drd.action.blocks.arguments.delta')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function setActionArguments(ActionBaseInterface $action, InputInterface $input) {
    parent::setActionArguments($action, $input);
    if ($module = $input->getOption('module')) {
      $action->setActionArgument('module', $module);
    }
    if ($delta = $input->getOption('delta')) {
      $action->setActionArgument('delta', $delta);
    }
  }

}
