<?php

/**
 * @file
 * Contains Drupal\po_translations_report\PoDetialsReporter.
 */

namespace Drupal\po_translations_report;

use Drupal\Component\Gettext\PoStreamReader;
use Drupal\Component\Utility\SafeMarkup;

/**
 * PoDetailsReporter class.
 */
class PoDetailsReporter {

  /**
   * Get detailed array per a po file.
   *
   * @param string $file
   *   The file.
   * @param string $category
   *   The category.
   *
   * @return array $results
   *   Array of results.
   */
  public function poReportDetails($file, $category) {
    $reader = new PoStreamReader();
    $reader->setURI($file);
    $reader->open();
    $results = array();
    while ($item = $reader->readItem()) {
      // Singular case.
      if (!$item->isPlural()) {
        $source = $item->getSource();
        $translation = $item->getTranslation();
        $singular_results = $this->categorize($category, $source, $translation);
        $results = array_merge($results, $singular_results);
      }
      else {
        // Plural case.
        $plural = $item->getTranslation();
        foreach ($item->getSource() as $key => $source) {
          $translation = $plural[$key];
          $plural_results = $this->categorize($category, $source, $translation);
          $results = array_merge($results, $plural_results);
        }
      }
    }
    return $results;
  }

  /**
   * Helper method to categorize strings in a po file.
   *
   * @param string $category
   *   The category.
   * @param string $source
   *   The source string.
   * @param string $translation
   *   The translation string.
   *
   * @return array $results
   *   Array of results;
   */
  public function categorize($category, $source, $translation) {
    $results = array();
    $safe_translation = locale_string_is_safe($translation);
    $translated = $translation != '';
    switch ($category) {
      case 'translated':
        if ($safe_translation && $translated) {
          $results[] = array(
            'source' => SafeMarkup::checkPlain($source),
            'translation' => SafeMarkup::checkPlain($translation),
          );
        }

        break;

      case 'untranslated':
        if ($safe_translation && !$translated) {
          $results[] = array(
            'source' => SafeMarkup::checkPlain($source),
            'translation' => SafeMarkup::checkPlain($translation),
          );
        }
        break;

      case 'not_allowed_translations':
        if (!$safe_translation) {
          $results[] = array(
            'source' => SafeMarkup::checkPlain($source),
            'translation' => SafeMarkup::checkPlain($translation),
          );
        }
        break;
    }
    return $results;
  }

}
