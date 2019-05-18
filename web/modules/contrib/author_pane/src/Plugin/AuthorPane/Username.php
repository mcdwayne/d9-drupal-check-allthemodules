<?php
/**
 * @file
 * Contains \Drupal\author_pane\Plugin\Username.
 */

namespace Drupal\author_pane\Plugin\AuthorPane;

use Drupal\author_pane\Plugin\AuthorPane\AuthorDatumBase;

/**
 * Provides the Username plugin.
 *
 * @AuthorPaneDatum(
 *   id = "username",
 *   label = @Translation("Username"),
 *   description = @Translation("Author's user name"),
 *   name = "username",
 * )
 */
class Username extends AuthorDatumBase {

  public function output() {
    // @TODO: Change this to the real output.
    return "Author's name is: " . $this->author->getUsername();
  }

}