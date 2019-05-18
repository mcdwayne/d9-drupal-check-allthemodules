<?php

namespace Drupal\drupal_git\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_git\GitRepository;

/**
 * Class DrupalGitUsersController.
 */
class DrupalGitUsersController extends ControllerBase {

  /**
   * Drupalgitallusersinfo.
   *
   * @return string
   *   Return Hello string.
   */
  public function drupalGitAllUsersInfo() {
    $repo = new GitRepository(__DIR__);
    return [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#title' => $this->t("List of all authors [total commits, username , emailid]"),
      '#items' => !is_null($repo->getUsersSummary()) ? $repo->getUsersSummary() : [t("No Author found.")],
      '#attributes' => ['class' => 'drupal-git'],
      '#wrapper_attributes' => ['class' => 'container'],
    ];
  }

}
