<?php

/**
 * @file
 * Provides a list of countries based on ISO standards.
 */

/**
 * Get an array of all country code => country name pairs.
 *
 * Get an array of all country code => country name pairs as laid out
 * in ISO 3166-1 alpha-2.
 * @return
 *   An array of all country code => country name pairs.
 */
function country_get_predefined_list() {
  $countries = \Drupal\Core\Locale\CountryManager::getStandardList();

  // Allow other modules to modify country list via
  // hook_smart_ip_country_predefined_list_alter()
  \Drupal::moduleHandler()->alter('smart_ip_country_predefined_list', $countries);

  return $countries;
}