<?php

/**
 * @file
 * Creates rules for serverside snowplow tracking.
 */

/**
 * Implements hook_rulse_actions_info()
 *
 * Declares any meta-data about actions for Rules in a big associative, nested
 * array. See also hook_rules_action_info in the rules.api.php file, and the
 * online documentation at http://drupal.org/node/878928
 */
function snowplow_rules_action_info() {

  $actions['snowplow_track_event'] = [
    'label' => t('Track snowplow events'),
    'group' => t('Snowplow'),
    'parameter' => [
      'event_label' => [
        'type' => 'string',
        'label' => t('The event label'),
      ],
    ],
  ];

  return $actions;
}

/**
 * @param type $event
 */
function snowplow_track_event($event) {

}
