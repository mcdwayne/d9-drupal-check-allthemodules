<?php

namespace Drupal\language_combination\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'language_combination_default' formatter.
 *
 * @FieldFormatter(
 *   id = "language_combination_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "language_combination",
 *   }
 * )
 */
class LanguageCombinationDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $installed_languages = \Drupal::languageManager()->getLanguages();
    foreach ($items as $delta => $item) {
      $source = $installed_languages[$item->language_source]->getName();
      $target = $installed_languages[$item->language_target]->getName();
      $elements[$delta]['#markup'] = $this->t('@source to @target', ['@source' => $source, '@target' => $target]);
    }

    return $elements;
  }

}
