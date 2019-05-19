<?php

namespace Drupal\yandex_oauth\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides the Yandex OAuth block.
 *
 * @Block(
 *   id = "yandex_oauth",
 *   admin_label = @Translation("Yandex OAuth"),
 * )
 */
class AuthBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#cache' => [
        'contexts' => ['user.permissions'],
        'tags' => ['config:yandex_oauth.settings'],
      ],
    ];

    $url = Url::fromRoute('yandex_oauth.auth', [], [
      'attributes' => ['rel' => 'nofollow'],
    ]);

    if ($url->access()) {
      $text = $this->t('Get/refresh access token from Yandex');
      $build['link'] = Link::fromTextAndUrl($text, $url)->toRenderable();
    }

    return $build;
  }

}
