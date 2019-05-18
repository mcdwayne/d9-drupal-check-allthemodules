<?php

namespace Drupal\achievements\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Most recent user achievement.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("latest_achievement")
 */
class LatestAchievement extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    $sanitized_value = $this->sanitizeValue($value);

    $latest = db_select('achievement_unlocks', 'au')
      ->fields('au', ['achievement_id', 'timestamp'])
      ->condition('uid', $sanitized_value)
      ->orderBy('timestamp', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchAll();

    if ($latest) {
      $latest_unlock = [
        '#theme' => 'achievement_latest_unlock',
        '#achievement_entity' => achievements_load($latest[0]->achievement_id),
        '#unlock' => ['timestamp' => $latest[0]->timestamp],
      ];

      $rendered = \Drupal::service('renderer')->renderRoot($latest_unlock);

      return $rendered;
    }

    return [];
  }

}
