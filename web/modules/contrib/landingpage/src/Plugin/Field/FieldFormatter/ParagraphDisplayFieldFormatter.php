<?php

namespace Drupal\landingpage\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

// Use Drupal\Core\Form\FormStateInterface;.
/**
 * Plugin implementation of the 'paragraph_display_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "paragraph_display_field_formatter",
 *   label = @Translation("Paragraph display field formatter"),
 *   field_types = {
 *     "paragraph_display_field_type"
 *   }
 * )
 */
class ParagraphDisplayFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      // Implement default settings.
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  /*public function settingsForm(array $form, FormStateInterface $form_state) {
  return array(
  // Implement settings form.
  ) + parent::settingsForm($form, $form_state);
  }*/

  /**
   * {@inheritdoc}
   */
  /*public function settingsSummary() {
  $summary = [];
  // Implement settings summary.

  return $summary;
  }*/

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#markup' => $item->value,
      );
    }

    return $elements;
  }

}
