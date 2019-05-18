<?php

/**
 * @file
 * Builds placeholder replacement tokens for library-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function libraries_provider_token_info() {
  $type = [
    'name' => t('Libraries'),
    'description' => t('Tokens related to individual library configurations.'),
    'needs-data' => 'library',
  ];

  // Core tokens for libraries.
  $library['variant'] = [
    'name' => t("Variant"),
    'description' => t('The current variant used for this library'),
  ];
  $library['version'] = [
    'name' => t("Version"),
    'description' => t('The current version used for this library'),
  ];

  return [
    'types' => ['library' => $type],
    'tokens' => ['library' => $library],
  ];
}

/**
 * Implements hook_tokens().
 */
function libraries_provider_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'library' && !empty($data['library'])) {
    /** @var \Drupal\libraries_provider\Entity\Library $library */
    $library = $data['library'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the library.
        case 'variant':
          $replacements[$original] = $library->get('variant');
          break;

        case 'version':
          $replacements[$original] = $library->get('version');
          break;

      }
    }
  }

  return $replacements;
}
