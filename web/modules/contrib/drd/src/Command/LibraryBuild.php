<?php

namespace Drupal\drd\Command;

/**
 * Class LibraryBuild.
 *
 * @package Drupal\drd
 */
class LibraryBuild extends BaseSystem {

  /**
   * Construct the LibraryBuild command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_library_build';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:library:build')
      ->setDescription($this->trans('commands.drd.action.library.build.description'));
  }

}
