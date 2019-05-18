<?php

/**
 * @file domain_finder.domains.inc
 * 
 * This file contains the array of domains extensions in configuration page.
 */

/**
 * Helper function to get domain extensions array.
 */
function domain_finder_get_domains() {
  $extensions = &drupal_static(__FUNCTION__);

  if (!isset($extensions)) {
    $extensions = [
      'basic' => [
        'title' => t('Basic domains'),
        'domains' => [
          'ae', 'aero', 'ag', 'asia', 'at', 'au', 'be', 'biz', 'br', 'ca',
          'cat', 'ch', 'cl', 'cn', 'co', 'com', 'coop', 'cz', 'de', 'edu',
          'es', 'eu', 'fi', 'fj', 'fm', 'fr', 'hu', 'ie', 'in', 'info', 'int',
          'ip', 'ip', 'ir', 'is', 'it', 'jp', 'lt', 'lu', 'ly', 'me', 'mobi',
          'museum', 'mx', 'name', 'net', 'nl', 'nu', 'nz', 'org', 'org', 'pl',
          'pro', 'pt', 'ro', 'ru', 'sc', 'se', 'si', 'su', 'tel', 'travel',
          'tv', 'uk', 'us', 've', 'ws', 'za', 'zanet',
        ],
      ],
    ];
  }

  return $extensions;
}
