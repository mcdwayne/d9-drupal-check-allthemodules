<?php

namespace Drupal\language_combination\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'language_combination_table' formatter.
 *
 * @FieldFormatter(
 *   id = "language_combination_table",
 *   label = @Translation("Table"),
 *   field_types = {
 *     "language_combination",
 *   }
 * )
 */
class LanguageCombinationTableFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $rows = [];

    $installed_languages = \Drupal::languageManager()->getLanguages();

    foreach ($items as $item) {
      $source = $installed_languages[$item->language_source]->getName();
      $target = $installed_languages[$item->language_target]->getName();
      $row[] = [
        'data' => $source,
        'class' => ['language-source', Html::getClass('language-' . $source)],
      ];

      $row[] = [
        'data' => $target,
        'class' => ['language-target', Html::getClass('language-' . $target)],
      ];

      $rows[] = [
        'data' => $row,
        'class' => [Html::getClass($source . '-' . $target)],
      ];
      $row = NULL;
    }

    return [
      '#theme' => 'table',
      '#header' => [$this->t('From'), $this->t('To')],
      '#rows' => $rows,
    ];

  }

}
