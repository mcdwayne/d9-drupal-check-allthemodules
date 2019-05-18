<?php

namespace Drupal\drd\Command;

use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MaintenanceMode.
 *
 * @package Drupal\drd
 */
class MaintenanceMode extends BaseDomain {

  /**
   * Construct the MaintenanceMode command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_maintenance_mode';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:maintenancemode')
      ->setDescription($this->trans('commands.drd.action.maintenancemode.description'))
      ->addArgument(
        'mode',
        InputArgument::REQUIRED,
        $this->trans('commands.drd.action.maintenancemode.arguments.mode')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    // Source argument.
    $mode = $input->getArgument('mode');
    if (!$mode) {
      $mode = $io->choice(
        $this->trans('commands.drd.action.maintenancemode.questions.source'),
        ['getStatus', 'on', 'off']
      );
      $input->setArgument('mode', $mode);
    }

    parent::interact($input, $output);
  }

  /**
   * {@inheritdoc}
   */
  protected function setActionArguments(ActionBaseInterface $action, InputInterface $input) {
    parent::setActionArguments($action, $input);
    $action->setActionArgument('mode', $input->getArgument('mode'));
  }

}
