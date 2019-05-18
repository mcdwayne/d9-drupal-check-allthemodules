<?php

namespace Drupal\br_address_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'br_address_plain_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "br_address_plain_formatter",
 *   label = @Translation("Brazilian address"),
 *   field_types = {
 *     "br_address_field_type"
 *   }
 * )
 */
class BrAddressPlainFormatter extends FormatterBase {

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
      $elements[$delta] = [
        '#markup' => $this->viewValue($item),
        '#theme' => 'br_address_field',
        '#postal_code' => $values['postal_code'],
        '#thoroughfare' => $values['thoroughfare'],
        '#number' => $values['number'],
        '#street_complement' => $values['street_complement'],
        '#neighborhood' => $values['neighborhood'],
        '#city' => $values['city'],
        '#state' => $values['state'],
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
    return nl2br(Html::escape($item->value));
  }

}
