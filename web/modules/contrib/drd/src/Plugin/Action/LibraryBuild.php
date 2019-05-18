<?php

namespace Drupal\drd\Plugin\Action;

/**
 * Provides a 'LibraryBuild' action.
 *
 * @Action(
 *  id = "drd_action_library_build",
 *  label = @Translation("Build the library"),
 *  type = "drd",
 * )
 */
class LibraryBuild extends BaseGlobal {

  /**
   * {@inheritdoc}
   */
  public function executeAction() {
    $args = [];
    \Drupal::service('drd.library.build')->build($args);
    return TRUE;
  }

}
