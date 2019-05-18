<?php

namespace Drupal\drupal_git\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_git\GitRepository;
use Drupal\drupal_git\GitException;

/**
 * Class DrupalGitOtherController.
 */
class DrupalGitOtherController extends ControllerBase {

  /**
   * Drupalgitotherinfo.
   *
   * @return string
   *   Return Hello string.
   */
  public function drupalGitOtherInfo() {
    $repo           = new GitRepository(__DIR__);
    $message        = \Drupal::messenger();
    $current_branch = NULL;
    try {
      $current_branch = $repo->getCurrentBranchName();
      $data           = [
        [
          '#markup' => $this->t("Last commit id in current branch: @commit_id", ['@commit_id' => $repo->getLastCommitId()]),
        ],
        [
          '#markup' => $this->t("Current Branch: @current_branch", ['@current_branch' => $current_branch]),
        ],
        [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#title' => $this->t("List of remote"),
          '#items' => $repo->getRemoteRepo(),
          '#attributes' => ['class' => 'drupal-git'],
          '#wrapper_attributes' => ['class' => 'container'],
        ],
        [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#title' => $this->t("List of all local branches"),
          '#items' => $repo->getLocalBranches(),
          '#attributes' => ['class' => 'drupal-git'],
          '#wrapper_attributes' => ['class' => 'container'],
        ],
      ];
    }
    catch (GitException $ex) {
      $message->addError($ex->getMessage());
      $data[] = [
        '#markup' => $ex->getMessage(),
      ];
    }
    return $data;
  }

}
