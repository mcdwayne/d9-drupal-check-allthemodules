<?php

namespace Drupal\country_state_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'contry_state_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "contry_state_formatter",
 *   label = @Translation("Contry state formatter"),
 *   field_types = {
 *     "country_state_type"
 *   }
 * )
 */
class ContryStateFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $values = $item->getValue();
      $country = entity_load('country', $values['country']);
      $state = entity_load('state', $values['state']);
      $city = entity_load('city', $values['city']);
      $elements[$delta] = [
        '#markup' => $this->viewValue($item),
        '#theme' => 'country_state_field',
        '#country' => !is_null($country) ? $country->getName() : '',
        '#state' => !is_null($state) ? $state->getName() : '',
        '#city' => !is_null($city) ? $city->getName() : '',
      ];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->country));
  }

}
