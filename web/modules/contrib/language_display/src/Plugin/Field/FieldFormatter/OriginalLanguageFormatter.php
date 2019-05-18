<?php

namespace Drupal\language_display\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\LanguageFormatter;

/**
 * Plugin implementation of the 'original_language' formatter.
 *
 * @FieldFormatter(
 *   id = "original_language",
 *   label = @Translation("Original language"),
 *   field_types = {
 *     "language"
 *   }
 * )
 */
class OriginalLanguageFormatter extends LanguageFormatter {
  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    $view['#plain_text'] = $this->languageManager
      ->getDefaultLanguage()
      ->getName();
    return $view;
  }

}
