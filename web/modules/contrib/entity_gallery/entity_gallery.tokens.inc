<?php

/**
 * @file
 * Builds placeholder replacement tokens for entity gallery-related data.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function entity_gallery_token_info() {
  $type = array(
    'name' => t('Entity galleries'),
    'description' => t('Tokens related to individual entity galleries.'),
    'needs-data' => 'entity_gallery',
  );

  // Core tokens for entity galleries.
  $entity_gallery['egid'] = array(
    'name' => t("Entity gallery ID"),
    'description' => t('The unique ID of the entity gallery.'),
  );
  $entity_gallery['vid'] = array(
    'name' => t("Revision ID"),
    'description' => t("The unique ID of the entity gallery's latest revision."),
  );
  $entity_gallery['type'] = array(
    'name' => t("Entity gallery type"),
  );
  $entity_gallery['type-name'] = array(
    'name' => t("Entity gallery type name"),
    'description' => t("The human-readable name of the entity gallery type."),
  );
  $entity_gallery['title'] = array(
    'name' => t("Title"),
  );
  $entity_gallery['body'] = array(
    'name' => t("Body"),
    'description' => t("The main body text of the entity gallery."),
  );
  $entity_gallery['summary'] = array(
    'name' => t("Summary"),
    'description' => t("The summary of the entity gallery's main body text."),
  );
  $entity_gallery['langcode'] = array(
    'name' => t('Language code'),
    'description' => t('The language code of the language the entity gallery is written in.'),
  );
  $entity_gallery['url'] = array(
    'name' => t("URL"),
    'description' => t("The URL of the entity gallery."),
  );
  $entity_gallery['edit-url'] = array(
    'name' => t("Edit URL"),
    'description' => t("The URL of the entity gallery's edit page."),
  );

  // Chained tokens for entity galleries.
  $entity_gallery['created'] = array(
    'name' => t("Date created"),
    'type' => 'date',
  );
  $entity_gallery['changed'] = array(
    'name' => t("Date changed"),
    'description' => t("The date the entity gallery was most recently updated."),
    'type' => 'date',
  );
  $entity_gallery['author'] = array(
    'name' => t("Author"),
    'type' => 'user',
  );

  return array(
    'types' => array('entity_gallery' => $type),
    'tokens' => array('entity_gallery' => $entity_gallery),
  );
}

/**
 * Implements hook_tokens().
 */
function entity_gallery_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }
  $replacements = array();

  if ($type == 'entity_gallery' && !empty($data['entity_gallery'])) {
    /** @var \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery */
    $entity_gallery = $data['entity_gallery'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the entity gallery.
        case 'egid':
          $replacements[$original] = $entity_gallery->id();
          break;

        case 'vid':
          $replacements[$original] = $entity_gallery->getRevisionId();
          break;

        case 'type':
          $replacements[$original] = $entity_gallery->getType();
          break;

        case 'type-name':
          $type_name = entity_gallery_get_type_label($entity_gallery);
          $replacements[$original] = $type_name;
          break;

        case 'title':
          $replacements[$original] = $entity_gallery->getTitle();
          break;

        case 'body':
        case 'summary':
          $translation = \Drupal::entityManager()->getTranslationFromContext($entity_gallery, $langcode, array('operation' => 'entity_gallery_tokens'));
          if ($translation->hasField('body') && ($items = $translation->get('body')) && !$items->isEmpty()) {
            $item = $items[0];
            // If the summary was requested and is not empty, use it.
            if ($name == 'summary' && !empty($item->summary)) {
              $output = $item->summary_processed;
            }
            // Attempt to provide a suitable version of the 'body' field.
            else {
              $output = $item->processed;
              // A summary was requested.
              if ($name == 'summary') {
                // Generate an optionally trimmed summary of the body field.

                // Get the 'trim_length' size used for the 'teaser' mode, if
                // present, or use the default trim_length size.
                $display_options = entity_get_display('entity_gallery', $entity_gallery->getType(), 'teaser')->getComponent('body');
                if (isset($display_options['settings']['trim_length'])) {
                  $length = $display_options['settings']['trim_length'];
                }
                else {
                  $settings = \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings('text_summary_or_trimmed');
                  $length = $settings['trim_length'];
                }

                $output = text_summary($output, $item->format, $length);
              }
            }
            // "processed" returns a \Drupal\Component\Render\MarkupInterface
            // via check_markup().
            $replacements[$original] = $output;
          }
          break;

        case 'langcode':
          $replacements[$original] = $entity_gallery->language()->getId();
          break;

        case 'url':
          $replacements[$original] = $entity_gallery->url('canonical', $url_options);
          break;

        case 'edit-url':
          $replacements[$original] = $entity_gallery->url('edit-form', $url_options);
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $account = $entity_gallery->getOwner() ? $entity_gallery->getOwner() : User::load(0);
          $bubbleable_metadata->addCacheableDependency($account);
          $replacements[$original] = $account->label();
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($entity_gallery->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($entity_gallery->getChangedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, array('user' => $entity_gallery->getOwner()), $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, array('date' => $entity_gallery->getCreatedTime()), $options, $bubbleable_metadata);
    }

    if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $changed_tokens, array('date' => $entity_gallery->getChangedTime()), $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
