<?php

namespace Drupal\klipfolio_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'klipfolio_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "klipfolio_field_summary_formatter",
 *   label = @Translation("Klipfolio summary"),
 *   field_types = {
 *     "klipfolio"
 *   }
 * )
 */
class KlipfolioFieldSummaryFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Short summary of Klip');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $summary = $item->title ?
        $item->title : $this->t('Klip:') . ' ' . $item->value;
      $elements[$delta] = [
        '#markup' => $summary
      ];
    }
    return $elements;
  }

}
