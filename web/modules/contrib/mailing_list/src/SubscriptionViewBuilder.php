<?php

namespace Drupal\mailing_list;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for subscriptions.
 */
class SubscriptionViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    /** @var \Drupal\mailing_list\SubscriptionInterface $entity */
    parent::alterBuild($build, $entity, $display, $view_mode);
    if ($entity->id()) {
      $build['#contextual_links']['subscription'] = [
        'route_parameters' => ['subscription' => $entity->id()],
        'metadata' => ['changed' => $entity->getChangedTime()],
      ];
    }

    // Prevent search engines from indexing subscriptions pages.
    $build['#attached']['html_head'][] = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'robots',
          'content' => 'noindex',
        ],
      ],
      'mailing_list',
    ];
  }

}
