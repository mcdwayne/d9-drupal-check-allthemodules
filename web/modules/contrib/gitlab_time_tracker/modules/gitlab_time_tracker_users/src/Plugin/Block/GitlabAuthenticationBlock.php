<?php

namespace Drupal\gitlab_time_tracker_users\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\LinkGeneratorTrait;

/**
 * Provides a 'GitlabAuthenticationBlock' block.
 *
 * @Block(
 *  id = "gitlab_authentication_block",
 *  admin_label = @Translation("Authenticate using Gitlab"),
 * )
 */
class GitlabAuthenticationBlock extends BlockBase {

  use LinkGeneratorTrait;
  use UrlGeneratorTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['content'] = [
      '#markup' =>
      '<div class="gitlab-authenticate">' .
      $this->l(
        $this->t('Sign in with Gitlab'),
        Url::fromRoute('gitlab_time_tracker_users.gitlab_authentication_controller_authenticate')
      ) . '</div>',
    ];

    return $build;
  }

}
