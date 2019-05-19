<?php

/**
 * @file
 * Provide views data for workflow_participants.module.
 */

/**
 * Implements hook_views_data().
 */
function workflow_participants_views_data() {

  $data['workflow_participants']['participant_role_field'] = [
    'title' => t('Participant role'),
    'field' => [
      'title' => t('Participant role'),
      'help' => t('The role of the participant.'),
      'id' => 'participant_role_field',
    ],
  ];

  $data['workflow_participants']['participant_role_filter'] = [
    'title' => t('Participant role'),
    'filter' => [
      'title' => t('Participant role filter'),
      'help' => t('The role of the participant.'),
      'id' => 'participant_role_filter',
    ],
  ];

  return $data;
}
