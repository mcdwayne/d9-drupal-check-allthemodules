<?php

/**
 * @file
 * Views related hook implementations.
 */

/**
 * Implements hook_views_data_alter().
 */
function mailing_list_views_data_alter(array &$data) {
  $data['mailing_list_subscription']['obfuscated_email'] = [
    'title' => t('Obfuscated subscription email'),
    'field' => [
      'title' => t('Email (obfuscated)'),
      'help' => t('The email address of this subscription (obfuscated).'),
      'id' => 'mailing_list_subscription_obfuscated_email',
    ],
  ];
}
