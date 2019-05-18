<?php

/**
 * @file
 * Rules integration for the ip2country module.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_condition_info().
 */
function ip2country_rules_condition_info() {
  $conditions = [];

  $conditions['ip2country_user_country'] = [
    'label' => t('User is in country (based on IP address)'),
    'group' => t('User'),
    'parameter' => [
      'countries' => [
        'label' => t('Countries'),
        'type' => 'list<text>',
        'options list' => 'country_get_list',
        'restriction' => 'input',
      ],
    ],
    'callbacks' => [
      'execute' => 'ip2country_user_country',
      'help' => 'ip2country_user_country_help',
    ],
  ];

  return $conditions;
}

/**
 * Help callback for ip2country_user_country condition.
 */
function ip2country_user_country_help() {
  return t('Uses the ip2country module to determine if the user is located in one of the selected countries.');
}

/**
 * Determines if the user's country is one of the selected countries.
 *
 * @param array $countries
 *   An array of ISO 3166-2 country codes to search.
 *
 * @return bool
 *   TRUE if the user's country is found in $countries, FALSE otherwise.
 */
function ip2country_user_country(array $countries = []) {
  $account  = \Drupal::currentUser();
  $userdata = \Drupal::service('user.data')->get('ip2country', $account->id(), 'country_iso_code_2');
  if (isset($userdata)) {
    // Use the country stored in the $user object.
    $country_code = $userdata;
  }
  else {
    // Determine the user's country based on IP address of the page request.
    $ip = \Drupal::request()->getClientIp();
    $country_code = \Drupal::service('ip2country.manager')->getCountry($ip);
  }

  return in_array($country_code, $countries);
}

/**
 * Implements hook_rules_action_info().
 */
function ip2country_rules_action_info() {
  $actions = [];

  $actions['ip2country_set_country'] = [
    'label' => t("Sets the user's country"),
    'group' => t('User'),
    'base' => 'ip2country_action_set_country',
    'parameter' => [
      'user' => [
        'type' => 'user',
        'label' => t('User'),
      ],
      'country' => [
        'type' => 'text',
        'label' => t('Country'),
        'options list' => 'country_get_list',
        'restriction' => 'input',
      ],
    ],
  ];

  return $actions;
}

/**
 * Sets the country_iso_code_2 property of the global $user object.
 */
function ip2country_action_set_country($account, $country_code) {
  // Store the ISO country code in the $user object.
  $account = user_load($account->id());
  $account->country_iso_code_2 = $country_code;
  \Drupal::service('user.data')->set('ip2country', $account->id(), 'country_iso_code_2', $country_code);
}

/**
 * @}
 */
