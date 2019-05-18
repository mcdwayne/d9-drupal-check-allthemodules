<?php

namespace Drupal\drd\Command;

use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class ReleaseLock.
 *
 * @package Drupal\drd
 */
class ReleaseLock extends BaseSystem {

  use BaseEntitySelect;

  protected $lockString = 'lock';

  /**
   * Construct the ProjectsStatus command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_release_' . $this->lockString;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:release:' . $this->lockString)
      ->setDescription($this->trans('commands.drd.action.release.' . $this->lockString . '.description'))
      ->addArgument(
        'projectName',
        InputArgument::REQUIRED,
        $this->trans('commands.drd.action.release.' . $this->lockString . '.arguments.projectname')
      )
      ->addArgument(
        'version',
        InputArgument::REQUIRED,
        $this->trans('commands.drd.action.release.' . $this->lockString . '.arguments.version')
      )
      ->configureSelection();
  }

  /**
   * {@inheritdoc}
   */
  protected function setActionArguments(ActionBaseInterface $action, InputInterface $input) {
    parent::setActionArguments($action, $input);
    $action->setActionArgument('projectName', $input->getArgument('projectName'));
    $action->setActionArgument('version', $input->getArgument('version'));
    $service = $this->getService($input);
    $criteria = $service->getSelectionCriteria();
    $action->setActionArgument('cores', empty($criteria) ? NULL : $service->cores());
  }

}
