<?php

/**
 * @file
 * Token related functions for the external_hreflang.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\external_hreflang\Event\ExternalHreflangGetCurrentUrlEvent;

/**
 * Implements hook_token_info().
 */
function external_hreflang_token_info() {
  $info = [];

  $languages = \Drupal::languageManager()->getLanguages();

  foreach ($languages as $language) {
    $args = ['@language' => $language->getName()];

    $info['tokens']['current-page']['url']['relative'][$language->getId()] = [
      'name' => t('URL in @name', $args),
      'description' => t('Relative URL of the current page in @language without language prefix.', $args),
    ];
  }

  return $info;
}

/**
 * Implements hook_tokens().
 */
function external_hreflang_tokens($type,
                                  $tokens,
                                  array $data,
                                  array $options,
                                  BubbleableMetadata $bubbleable_metadata) {

  $replacements = [];

  if ($type == 'current-page') {
    $languages = \Drupal::languageManager()->getLanguages();

    foreach ($languages as $language) {
      $tokens_to_process['[current-page:url:relative:' . $language->getId() . ']'] = $language;
    }

    foreach ($tokens as $token) {
      if (isset($tokens_to_process[$token])) {
        $language = $tokens_to_process[$token];
        $url = _external_hreflang_get_url_from_request();
        $url->setOption('language', $language);
        $replacements[$token] = str_replace('/' . $language->getId(), '', $url->toString());
      }
    }
  }

  return $replacements;
}

/**
 * Get url object for current request.
 *
 * Allow other modules to provide the url and cache statically for same request.
 *
 * @return \Drupal\Core\Url
 *   Url object.
 */
function _external_hreflang_get_url_from_request() {
  static $url;

  if (empty($url)) {
    $event = new ExternalHreflangGetCurrentUrlEvent();
    \Drupal::service('event_dispatcher')->dispatch(ExternalHreflangGetCurrentUrlEvent::EVENT_NAME, $event);
    $url = $event->getCurrentUrl();
  }

  return $url;
}
