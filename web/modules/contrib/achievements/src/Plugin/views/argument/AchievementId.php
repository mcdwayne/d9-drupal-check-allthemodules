<?php

namespace Drupal\achievements\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Standard;

/**
 * Default implementation of the base argument plugin.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("achievement_id")
 */
class AchievementId extends Standard {

  /**
   * Get the title this argument will assign the view, given the argument.
   *
   * This usually needs to be overridden to provide a proper title.
   */
  public function title() {
    $achievement_id = $this->argument;
    $config = \Drupal::config("achievements.achievement_entity.$achievement_id");
    $label = $config->get('label');

    return $label;
  }

}
