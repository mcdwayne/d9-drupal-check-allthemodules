<?php

/**
 * @file
 * Builds placeholder replacement tokens for webforms and submissions.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\user\Entity\User;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;
use Drupal\webform\Plugin\WebformElement\WebformComputedBase;
use Drupal\webform\Utility\WebformDateHelper;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function file_download_token_webform_token_info_alter(&$data) {
  $data['tokens']['webform']['file-download-token-url'] = [
    'name' => t('Webform file download url'),
    'description' => t('Displays a file download token link if one is specified for this webform in an email handler.'),
  ];
}

/**
 * Implements hook_tokens().
 */
function file_download_token_webform_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  // Set URL options to generate absolute translated URLs.
  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  $replacements = [];
  if ($type == 'webform' && !empty($data['webform'])) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $data['webform'];
    foreach ($tokens as $name => $original) {
      if ($name == 'file-download-token-url') {
        if ($settings = $webform->getThirdPartySettings('file_download_token_webform')) {
          $entity_type_manager = \Drupal::entityTypeManager()->getStorage('file');
          $download_token_manager = \Drupal::service('file_download_token.download_token_manager');

          if ($settings['download_token_file']) {
            if($file = $entity_type_manager->load($settings['download_token_file'])) {
              $replacements[$original] = $download_token_manager->createTokenUrl($file)->toString();
            }
          }
        }
      }
    }
  }

  return $replacements;
}
