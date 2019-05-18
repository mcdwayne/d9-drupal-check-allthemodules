<?php

namespace Drupal\drd\Command;

use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ProjectsUpdateLog.
 *
 * @package Drupal\drd
 */
class ProjectsUpdateLog extends BaseCore {

  /**
   * Construct the ProjectsUpdateLog command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_projects_update_log';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:projects:update:log')
      ->setDescription($this->trans('commands.drd.action.projects.update.log.description'))
      ->addOption(
        'id',
        NULL,
        InputOption::VALUE_NONE,
        $this->trans('commands.drd.action.projects.update.log.arguments.id')
      )
      ->addOption(
        'list',
        NULL,
        InputOption::VALUE_NONE,
        $this->trans('commands.drd.action.projects.update.log.arguments.list')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function setActionArguments(ActionBaseInterface $action, InputInterface $input) {
    parent::setActionArguments($action, $input);
    $id = $input->getOption('id');
    if (isset($id)) {
      $action->setActionArgument('id', $id);
    }
    $list = $input->getOption('list');
    if (isset($list)) {
      $action->setActionArgument('list', $list);
    }
  }

}
