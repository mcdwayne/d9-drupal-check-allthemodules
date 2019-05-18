<?php

namespace Drupal\language_code_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\LanguageFormatter;
use Drupal\Core\Language\LanguageInterface;

/**
 * Plugin implementation of the 'language_code' formatter.
 *
 * @FieldFormatter(
 *   id = "language_code",
 *   label = @Translation("Language Code"),
 *   field_types = {
 *     "language"
 *   }
 * )
 */
class LanguageCodeFormatter extends LanguageFormatter {

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    // The 'languages' cache context is not necessary because the language is
    // either displayed in its configured form (loaded directly from config
    // storage by LanguageManager::getLanguages()) or in its native language
    // name. That only depends on formatter settings and no language condition.
    $languages = $this->getSetting('native_language') ? $this->languageManager->getNativeLanguages(LanguageInterface::STATE_ALL) : $this->languageManager->getLanguages(LanguageInterface::STATE_ALL);
    return [
      '#plain_text' => $item->language && isset($languages[$item->language->getId()]) ? $item->language->getId() : ''
    ];
  }

}
