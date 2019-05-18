<?php

/**
 * @file
 * Builds placeholder replacement tokens for merci_line_item-related data.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function merci_line_item_token_info() {
  $type = [
    'name' => t('Merci'),
    'description' => t('Tokens related to individual content items, or "merci_line_items".'),
    'needs-data' => 'merci_line_item',
  ];

  // Core tokens for merci_line_items.
  $merci_line_item['id'] = [
    'name' => t("Content ID"),
    'description' => t('The unique ID of the content item, or "merci_line_item".'),
  ];
  $merci_line_item['label'] = [
    'name' => t("Title"),
  ];

  return [
    'types' => ['merci_line_item' => $type],
    'tokens' => ['merci_line_item' => $merci_line_item],
  ];
}

/**
 * Implements hook_tokens().
 */
function merci_line_item_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }
  $replacements = [];

  if ($type == 'merci_line_item' && !empty($data['merci_line_item'])) {
    /** @var \Drupal\merci_line_item\NodeInterface $merci_line_item */
    $merci_line_item = $data['merci_line_item'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the merci_line_item.
        case 'id':
          $replacements[$original] = $merci_line_item->id();
          break;

        case 'label':
          $replacements[$original] = $merci_line_item->label();
          break;
      }
    }

  }

  return $replacements;
}
