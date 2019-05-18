<?php

namespace Drupal\drd\Command;

/**
 * Class ProjectsStatus.
 *
 * @package Drupal\drd
 */
class ProjectsStatus extends BaseSystem {

  /**
   * Construct the ProjectsStatus command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_projects_status';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:projects:status')
      ->setDescription($this->trans('commands.drd.action.projects.status.description'));
  }

}
