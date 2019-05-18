<?php

namespace Drupal\drupal_git\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_git\GitRepository;
use Drupal\drupal_git\GitException;

/**
 * Class drupalGitAllLogsController.
 */
class DrupalGitAllLogsController extends ControllerBase {

  /**
   * Drupalgitalllogs.
   *
   * @return string
   *   Return Hello string.
   */
  public function drupalGitAllLogs() {
    try {
      $repo         = new GitRepository(__DIR__);
      $total        = count($repo->getAllLogs());
      $num_per_page = 80;
      // Initialize pager and gets current page.
      $current_page = pager_default_initialize($total, $num_per_page);
      $chunks       = array_chunk($repo->getAllLogs(), $num_per_page);

      // Get the items for our current page.
      $current_page_items  = $chunks[$current_page];
      $render              = [];
      $render[]            = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => $this->t("GIT Logs"),
        '#items' => $current_page_items,
        '#attributes' => [
          'class' => [
            'drupal-git',
            'drupal-git-all-logs',
          ],
        ],
        '#wrapper_attributes' => [
          'class' => 'container',
        ],
      ];
      $render['#attached'] = [
        'library' =>
        'drupal_git/drupal_git_global',
      ];
      $render[]            = ['#type' => 'pager'];
    }
    catch (GitException $ex) {
      \Drupal::messenger()->addError($ex->getMessage());
      $render[] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => $this->t("GIT Logs"),
        '#items' => [t("No logs found")],
        '#attributes' => [
          'class' => [
            'drupal-git',
            'drupal-git-no-data',
          ],
        ],
        '#wrapper_attributes' => [
          'class' => 'container',
        ],
      ];
    }
    return $render;
  }

}
