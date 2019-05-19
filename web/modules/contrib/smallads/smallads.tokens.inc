<?php

/**
 * @file
 * Token hooks.
 */

use Drupal\smallads\Entity\SmalladType;
use Drupal\Core\Language\LanguageInterface;

/**
 * Implements hook_tokens().
 */
function smallads_tokens($type, $tokens, array $data = [], array $options = []) {

  if (!array_key_exists($type, $data) || !is_object($data[$type])) {
    return;
  }

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }


  $replacements = [];
  if ($type == 'smallad') {
    $smallad = $data['smallad'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'type':
          $replacements[$original] =$smallad->bundle();
          break;
        case 'type-name':
          $replacements[$original] = SmalladType::load($smallad->bundle())->label();
          break;
        case 'smid':
          $replacements[$original] = $smallad->id();
          break;
        case 'title':
          $replacements[$original] = $smallad->label();
          break;
        case 'body':
          $replacements[$original] = $smallad->body->value;
          break;
        case 'created':
          $replacements[$original] = \Drupal::service('date.formatter')->format($smallad->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;
        case 'expires':
          $replacements[$original] = \Drupal::service('date.formatter')->format($smallad->expires->value, 'medium', '', NULL, $langcode);
          break;
        case 'url':
          $replacements[$original] = $smallad->toLink();
          break;
        case 'edit-url':
          $replacements[$original] = $smallad->url('edit-form', $url_options);
          break;
        case 'link':
          $replacements[$original] = $smallad->link();
          break;
      }
    }
  }
  return $replacements;
}

function smallad_token_info() {
  $type = [
    'name' => t('Smallads'),
    'description' => t('Tokens related to individual small ads.'),
    'needs-data' => 'smallad',
  ];

  // Core tokens for nodes.
  $smallad['smid'] = [
    'name' => t("Small ad ID"),
    'description' => t('The unique ID of the ad.'),
  ];
  $smallad['type'] = [
    'name' => t("The bundle name of the smallad type"),
  ];
  $smallad['type-name'] = [
    'name' => t("The name of the smallad type"),
  ];
  $smallad['title'] = [
    'name' => t("the short title of the ad"),
  ];

  $smallad['body'] = [
    'name' => t("Body"),
    'description' => t("The main body text of the node."),
  ];
  $smallad['owner'] = [
    'name' => t("Owner"),
    'type' => 'user',
  ];

  // Chained tokens for nodes.
  $smallad['created'] = [
    'name' => t("Date created"),
    'type' => 'date',
  ];
  // Chained tokens for nodes.
  $smallad['expires'] = [
    'name' => t("Date created"),
    'type' => 'date',
  ];
  $smallad['url'] = [
    'name' => t("URL"),
    'description' => t("The absolute URL of the ad."),
  ];
  $smallad['edit-url'] = [
    'name' => t("Edit URL"),
    'description' => t("The URL of the ad's edit page."),
  ];

  return [
    'types' => ['smallad' => $type],
    'tokens' => ['smallad' => $smallad],
  ];
}
