<?php

namespace Drupal\drupal_git\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_git\GitRepository;
use Drupal\drupal_git\GitException;

/**
 * Class drupalGitAllRepoController.
 */
class DrupalGitAllRepoController extends ControllerBase {

  /**
   * Drupalgitallrepo.
   *
   * @return string
   *   Return Hello string.
   */
  public function drupalGitAllRepo() {
    $repo           = new GitRepository(__DIR__);
    $message        = \Drupal::messenger();
    $current_branch = NULL;
    try {
      $current_branch = $repo->getCurrentBranchName();
      $data[]         = [
        [
          '#markup' => $this->t("List of all repository branches (remotes & locals) ( Current Branch: @current )", ['@current' => $current_branch]),
        ],
        [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#title' => $this->t("Branches"),
          '#items' => $repo->getBranches(),
          '#attributes' => [
            'class' => [
              'drupal-git', 'drupal-git-all-logs',
            ],
          ],
          '#wrapper_attributes' => [
            'class' => 'container',
          ],
        ],
      ];
    }
    catch (GitException $ex) {
      $message->addError($ex->getMessage());
      $data['markup'] = [
        '#type' => 'markup',
        '#markup' => $ex->getMessage(),
      ];
    }
    return $data;
  }

}
