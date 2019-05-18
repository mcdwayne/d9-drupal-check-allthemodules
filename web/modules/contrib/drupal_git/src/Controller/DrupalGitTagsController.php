<?php

namespace Drupal\drupal_git\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_git\GitRepository;

/**
 * Class DrupalGitTagsController.
 */
class DrupalGitTagsController extends ControllerBase {

  /**
   * Drupalgittags.
   *
   * @return string
   *   Return Hello string.
   */
  public function drupalGitTags() {
    $repo = new GitRepository(__DIR__);
    return [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#title' => $this->t("List of all tags in repository"),
      '#items' => empty($repo->getTags()) ? [$this->t("No Tags Found.")] : $repo->getTags(),
      '#attributes' => ['class' => 'drupal-git'],
      '#wrapper_attributes' => ['class' => 'container'],
    ];
  }

}
