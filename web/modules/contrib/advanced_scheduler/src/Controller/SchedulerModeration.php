<?php

namespace Drupal\advanced_scheduler\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\workbench_moderation\Entity\ModerationState;

/**
 * Class SchedulerModeration.
 *
 * @package Drupal\advanced_scheduler\Controller\SchedulerModeration
 */
class SchedulerModeration extends ControllerBase {

  /**
   * Save scheduled states in configuration.
   *
   * @method saveScheduledStatesConfig
   */
  public static function saveScheduledStates($states_key) {
    \Drupal::configFactory()->getEditable('advanced_scheduler.settings')
      ->set('state_transition', $states_key)
      ->save();
  }

  /**
   * Get all workbench moderation states.
   *
   * @method getAllWorkbenchModerationStates
   */
  public static function getAllWorkbenchModerationStates() {
    $moderationStates = ModerationState::loadMultiple();
    foreach ($moderationStates as $moderation_state) {
      if ($moderation_state->id() != 'published') {
        $options[$moderation_state->id()] = $moderation_state->label();
      }
    }

    return $options;
  }

  /**
   * Get submitted configuration form transition states.
   *
   * @method getConfigTransitionState
   */
  public static function getConfigTransitionState($state_transition) {
    $transitions = [];
    foreach ($state_transition as $state_key => $state_value) {
      if ($state_value == $state_key && $state_value !== 0) {
        $transitions[] = $state_key;
      }
    }

    return $transitions;
  }

  /**
   * Get saved configuration moderation states.
   *
   * @method getScheduledConfig
   */
  public static function getScheduledConfig() {
    return \Drupal::config('advanced_scheduler.settings')
      ->get('state_transition');
  }

}
