<?php

namespace Drupal\obfuscate\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\obfuscate\ObfuscateMailFactory;

/**
 * Plugin implementation of the 'obfuscate_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "obfuscate_field_formatter",
 *   label = @Translation("Obfuscate"),
 *   field_types = {
 *     "email"
 *   }
 * )
 */
class ObfuscateFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    // Gets the default Field Formatter settings
    // from the system wide configuration.
    $config = \Drupal::config('obfuscate.settings');
    $method = $config->get('obfuscate.method');
    return [
      'obfuscate_method' => $method,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['obfuscate_method'] = [
      '#title' => t('Obfuscation method'),
      '#type' => 'radios',
      '#options' => [
        ObfuscateMailFactory::HTML_ENTITY => $this->t('HTML entity'),
        ObfuscateMailFactory::ROT_13 => $this->t('ROT 13 / reversed text'),
      ],
      // Field override, gets default from system wide configuration.
      '#default_value' => $this->getSetting('obfuscate_method'),
    ];
    return $form + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $method = $this->getSetting('obfuscate_method');
    switch ($method) {
      case ObfuscateMailFactory::HTML_ENTITY:
        $summary[] = $this->t('Obfuscates email address with HTML entities (PHP only).');
        break;

      case ObfuscateMailFactory::ROT_13:
        $summary[] = $this->t('Obfuscates email address with ROT 13 and reversed text (PHP/Javascript ROT 13, with reversed text CSS fallback).');
        break;
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $obfuscateMail = ObfuscateMailFactory::get($this->getSetting('obfuscate_method'));
    foreach ($items as $delta => $item) {
      $elements[$delta] = $obfuscateMail->getObfuscatedLink($item->value);
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
