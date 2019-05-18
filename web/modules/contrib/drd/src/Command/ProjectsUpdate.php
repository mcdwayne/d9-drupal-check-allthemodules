<?php

namespace Drupal\drd\Command;

use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ProjectsUpdate.
 *
 * @package Drupal\drd
 */
class ProjectsUpdate extends BaseCore {

  /**
   * Construct the ProjectsUpdate command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_projects_update';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:projects:update')
      ->setDescription($this->trans('commands.drd.action.projects.update.description'))
      ->addOption(
        'dry-run',
        NULL,
        InputOption::VALUE_NONE,
        $this->trans('commands.drd.action.projects.update.arguments.dryrun')
      )
      ->addOption(
        'show-log',
        NULL,
        InputOption::VALUE_NONE,
        $this->trans('commands.drd.action.projects.update.arguments.showlog')
      )
      ->addOption(
        'list',
        NULL,
        InputOption::VALUE_NONE,
        $this->trans('commands.drd.action.projects.update.arguments.list')
      )
      ->addOption(
        'include-locked',
        NULL,
        InputOption::VALUE_NONE,
        $this->trans('commands.drd.action.projects.update.arguments.includelocked')
      )
      ->addOption(
        'security-only',
        NULL,
        InputOption::VALUE_NONE,
        $this->trans('commands.drd.action.projects.update.arguments.securityonly')
      )
      ->addOption(
        'force-locked-security',
        NULL,
        InputOption::VALUE_NONE,
        $this->trans('commands.drd.action.projects.update.arguments.forcelockedsecurity')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function setActionArguments(ActionBaseInterface $action, InputInterface $input) {
    parent::setActionArguments($action, $input);
    $dry = $input->getOption('dry-run');
    if (isset($dry)) {
      $action->setActionArgument('dry-run', $dry);
    }
    $showlog = $input->getOption('show-log');
    if (isset($showlog)) {
      $action->setActionArgument('show-log', $showlog);
    }
    $list = $input->getOption('list');
    if (isset($list)) {
      $action->setActionArgument('list', $list);
    }
    $includeLocked = $input->getOption('include-locked');
    if (isset($includeLocked)) {
      $action->setActionArgument('include-locked', $includeLocked);
    }
    $securityOnly = $input->getOption('security-only');
    if (isset($securityOnly)) {
      $action->setActionArgument('security-only', $securityOnly);
    }
    $forceLockedSecurity = $input->getOption('force-locked-security');
    if (isset($forceLockedSecurity)) {
      $action->setActionArgument('force-locked-security', $forceLockedSecurity);
    }
  }

}
