<?php

namespace Drupal\drd\Command;

/**
 * Class Projects.
 *
 * @package Drupal\drd
 */
class Projects extends BaseDomain {

  /**
   * Construct the Projects command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_projects';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:projects:usage')
      ->setDescription($this->trans('commands.drd.action.projects.usage.description'));
  }

}
