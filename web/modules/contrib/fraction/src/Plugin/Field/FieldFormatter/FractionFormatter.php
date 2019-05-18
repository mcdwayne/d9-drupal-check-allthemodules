<?php

namespace Drupal\fraction\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'fraction' formatter.
 *
 * @FieldFormatter(
 *   id = "fraction",
 *   label = @Translation("Fraction"),
 *   field_types = {
 *     "fraction"
 *   }
 * )
 */
class FractionFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'separator' => '/',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    // Numerator and denominator separator.
    $elements['separator'] = array(
      '#type' => 'textfield',
      '#title' => t('Separator'),
      '#description' => t('Specify the separator to display between the numerator and denominator.'),
      '#default_value' => $this->getSetting('separator'),
      '#required' => TRUE,
      '#weight' => 0,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    // Summarize the separator setting.
    $separator = $this->getSetting('separator');
    $summary[] = t('Separator: @separator', array(
      '@separator' => $separator,
    ));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    // Load the separator setting.
    $separator = $this->getSetting('separator');

    // Iterate through the items.
    foreach ($items as $delta => $item) {

      // Output fraction as a string.
      $elements[$delta] = array(
        '#markup' => $item->fraction->toString($separator),
      );
    }

    return $elements;
  }
}
