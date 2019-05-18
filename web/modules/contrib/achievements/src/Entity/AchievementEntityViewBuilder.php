<?php

namespace Drupal\achievements\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 *
 */
class AchievementEntityViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);
    $build['#theme'] = 'achievement';
    // Determine if current user has unlocked.
    $build['#unlock'] = achievements_unlocked_already($entity->id());
    $build['#attached'] = [
      'library' => [
        'achievements/achievements',
      ],
    ];

    return $build;
  }

}
