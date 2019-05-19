<?php

/**
 * @file
 * Provide views runtime hooks for translation_views.module.
 *
 * Provides views target language substitution.
 */

use Drupal\Core\Language\LanguageInterface;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_substitutions().
 */
function translation_views_views_query_substitutions(ViewExecutable $view) {
  $inputs    = $view->getExposedInput();
  $langcodes = array_keys(\Drupal::languageManager()->getLanguages(
    LanguageInterface::STATE_CONFIGURABLE
  ));

  if (isset($inputs['translation_target_language'])
    && in_array($inputs['translation_target_language'], $langcodes)
  ) {
    $langcode = $inputs['translation_target_language'];
  }
  else {
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
  }

  $substitutions = [
    '***TRANSLATION_VIEWS_TARGET_LANG***' => $langcode,
  ];

  return $substitutions;
}
