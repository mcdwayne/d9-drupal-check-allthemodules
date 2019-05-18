<?php

/**
 * @file
 * Builds placeholder replacement tokens for log-related data.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function log_token_info() {
  $type = array(
    'name' => t('Logs'),
    'description' => t('Tokens related to individual log items.'),
    'needs-data' => 'log',
  );

  // Core tokens for logs.
  $log['id'] = array(
    'name' => t("Log ID"),
    'description' => t('The unique ID of the log item.'),
  );
  $log['vid'] = array(
    'name' => t("Revision ID"),
    'description' => t("The unique ID of the log's latest revision."),
  );
  $log['type'] = array(
    'name' => t("Log type"),
  );
  $log['type-name'] = array(
    'name' => t("Log type name"),
    'description' => t("The human-readable name of the log type."),
  );
  $log['name'] = array(
    'name' => t("Name"),
  );
  $log['url'] = array(
    'name' => t("URL"),
    'description' => t("The URL of the log."),
  );
  $log['edit-url'] = array(
    'name' => t("Edit URL"),
    'description' => t("The URL of the log's edit page."),
  );

  // Chained tokens for logs.
  $log['created'] = array(
    'name' => t("Date created"),
    'type' => 'date',
  );
  $log['changed'] = array(
    'name' => t("Date changed"),
    'description' => t("The date the log was most recently updated."),
    'type' => 'date',
  );
  $log['author'] = array(
    'name' => t("Author"),
    'type' => 'user',
  );

  return array(
    'types' => array('log' => $type),
    'tokens' => array('log' => $log),
  );
}

/**
 * Implements hook_tokens().
 */
function log_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
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

  if ($type == 'log' && !empty($data['log'])) {
    /** @var \Drupal\log\LogInterface $log */
    $log = $data['log'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the log.
        case 'id':
          $replacements[$original] = $log->id();
          break;

        case 'vid':
          $replacements[$original] = $log->getRevisionId();
          break;

        case 'type':
          $replacements[$original] = $log->getType();
          break;

        case 'type-name':
          $replacements[$original] = $log->getTypeName();
          break;

        case 'title':
          $replacements[$original] = $log->label();
          break;

        case 'langcode':
          $replacements[$original] = $log->language()->getId();
          break;

        case 'url':
          $replacements[$original] = $log->toUrl('canonical', $url_options);
          break;

        case 'edit-url':
          $replacements[$original] = $log->toUrl('edit-form', $url_options);
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $account = $log->getOwner() ? $log->getOwner() : User::load(0);
          $bubbleable_metadata->addCacheableDependency($account);
          $replacements[$original] = $account->label();
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($log->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($log->getChangedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, array('user' => $log->getOwner()), $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, array('date' => $log->getCreatedTime()), $options, $bubbleable_metadata);
    }

    if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $changed_tokens, array('date' => $log->getChangedTime()), $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
