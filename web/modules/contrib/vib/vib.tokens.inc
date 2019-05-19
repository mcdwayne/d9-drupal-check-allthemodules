<?php

/**
 * @file
 * Builds placeholder replacement token for 'View in browser' module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function vib_token_info() {
  $types['vib'] = [
    'name' => t('View in browser'),
    'description' => t('Define token for "View in browser" email link.'),
  ];
  $tokens['link'] = [
    'name' => t('Link'),
    'description' => t('View in browser link.'),
  ];

  return [
    'types' => $types,
    'tokens' => [
      'vib' => $tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function vib_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'vib' && isset($data['vib_link'])) {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'link':
          /** @var \Drupal\vib\Entity\VibLinkInterface $vib */
          $vib = $data['vib_link'];

          $args = ['id' => $vib->id(), 'token' => $vib->getToken()];
          $url_options = [
            'language' => \Drupal::languageManager()
              ->getLanguage($options['langcode']),
          ];
          $url = Url::fromRoute('vib.page', $args, $url_options);
          $url->setAbsolute();
          $link = [
            '#type' => 'link',
            '#attributes' => ['rel' => 'nofollow'],
            '#title' => t('View in browser', [], ['langcode' => $options['langcode']]),
            '#url' => $url,
          ];

          $link = \Drupal::service('renderer')->render($link);
          $replacements[$original] = $link;
          break;

        default:
      }
    }
  }

  return $replacements;
}
