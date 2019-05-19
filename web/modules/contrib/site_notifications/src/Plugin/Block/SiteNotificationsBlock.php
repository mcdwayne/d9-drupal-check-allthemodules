<?php

namespace Drupal\site_notifications\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\site_notifications\SiteNotificationsHelper;

/**
 * Provides a site_notification_block.
 *
 * @Block(
 *   id = "site_notification_block",
 *   admin_label = @Translation("Site Notifications"),
 *   category = @Translation("Custom notification block")
 * )
 */
class SiteNotificationsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * Overrides default functio for no caching for block contents.
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_content = SiteNotificationsHelper::getNotificationsData();

    if (empty($block_content['count'])) {
      $count = 0;
    }
    else {
      $count = $block_content['count'];
    }

    return [
      '#title'              => $this->t('Notifications (@count)', ['@count' => $count]),
      '#type'               => 'markup',
      '#theme'              => 'notifications',
      '#notifications'      => $block_content['output'],
      '#notification_count' => $block_content['count'],
      '#link'               => $block_content['link'],
      '#attached' => [
        'library' => ['site_notifications/site_notifications'],
        'drupalSettings' => [
          'site_notifications' => [
            'refresh_interval'  => $block_content['refresh_interval'],
            'notify_status'     => $block_content['notify_status'],
            'user_access'       => $block_content['user_access'],
          ],
        ],
      ],
      'cache' => [
        'max_age' => 0,
      ],
    ];
  }

}
