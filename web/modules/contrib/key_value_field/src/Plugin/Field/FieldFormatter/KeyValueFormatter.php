<?php

namespace Drupal\key_value_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'key_value' formatter.
 *
 * @FieldFormatter(
 *   id = "key_value",
 *   label = @Translation("Key Value"),
 *   field_types = {
 *     "key_value",
 *     "key_value_long",
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class KeyValueFormatter extends TextDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['value_only'] = FALSE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Get the default textfield form.
    $form = parent::settingsForm($form, $form_state);
    // Allow the formatter to hide the key.
    $form['value_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Value only'),
      '#default_value' => $this->getSetting('value_only'),
      '#description' => $this->t('Make the formatter hide the "Key" part of the field and display the "Value" only.'),
      '#weight' => 4,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $key = $this->getSetting('value_only') ? '' : ' [Key] : ';

    // Add a summary for the key field.
    $summary[] = t('Display format: @key [Value].', ['@key' => $key]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Get the value elements from the TextDefaultFormatter class.
    $value_elements = parent::viewElements($items, $langcode);

    // Buffer the return value.
    $elements = [];
    // Loop through all items.
    foreach ($items as $delta => $item) {
      // Just add the key element to the render array, when 'value_only' is not
      // checked.
      if (!$this->getSetting('value_only')) {
        $elements[$delta]['key'] = [
          '#markup' => nl2br(SafeMarkup::checkPlain($item->key . ' : ')),
        ];
      }
      // Add the value to the render array.
      $elements[$delta]['value'] = $value_elements[$delta];
    }
    return $elements;
  }

}
