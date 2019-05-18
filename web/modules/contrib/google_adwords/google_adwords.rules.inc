<?php

/**
 * @file
 * Rules integration for google adwords conversion tracking.
 */

/**
 * Implements hook_rules_action_info().
 *
 * Use this rule to add google adword conversion tracking on non entity pages.
 */
function google_adwords_rules_action_info() {
  $actions = [];

  $actions['google_adwords_add_tracking'] = [
    'label' => t('Set Google Adwords conversion tracking code'),
    'parameter' => [
      'conversion_id' => [
        'type' => 'integer',
        'label' => t('Conversion ID'),
        'optional' => FALSE,
      ],
      'label' => [
        'type' => 'text',
        'label' => t('Label'),
        'optional' => FALSE,
      ],
      'value' => [
        'type' => 'text',
        'label' => t('Conversion Value'),
        'optional' => TRUE,
        'default value' => 0,
      ],
      'language' => [
        'type' => 'text',
        'label' => t('Language'),
        'optional' => TRUE,
        'default value' => 'en',
      ],
      'color' => [
        'type' => 'text',
        'label' => t('Color'),
        'optional' => TRUE,
        'default value' => 'FFFFFF',
      ],
      'format' => [
        'type' => 'text',
        'label' => t('Format'),
        'optional' => TRUE,
        'default value' => '1',
      ],
    ],
    'group' => t('Google Adwords'),
  ];
  return $actions;
}
