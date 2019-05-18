<?php

namespace Drupal\drupal_git\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_git\GitRepository;
use Drupal\drupal_git\GitException;

/**
 * Class drupalGitLogController.
 */
class DrupalGitLogController extends ControllerBase {

  /**
   * Drupalgitlog.
   *
   * @return string
   *   Return Hello string.
   */
  public function drupalGitLog() {
    $repo = new GitRepository(__DIR__);
    try {
      $render              = [];
      $render              = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => $this->t("Git Pretty Logs"),
        '#items' => !is_null($repo->getPrettyLogs()) ? $repo->getPrettyLogs() : ["No Logs Found."],
        '#attributes' => [
          'class' => [
            'drupal-git',
            'drupal-git-logs',
          ],
        ],
        '#wrapper_attributes' => [
          'class' => 'container',
        ],
      ];
      $render['#attached'] = [
        'library' => 'drupal_git/drupal_git_global',
      ];
    }
    catch (GitException $ex) {
      \Drupal::messenger()->addError($ex->getMessage());
      $render = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => $this->t("Git Pretty Logs"),
        '#items' => ["No Logs Found."],
        '#attributes' => [
          'class' => [
            'drupal-git',
            'drupal-git-no-data',
          ],
        ],
      ];
    }
    return $render;
  }

}
