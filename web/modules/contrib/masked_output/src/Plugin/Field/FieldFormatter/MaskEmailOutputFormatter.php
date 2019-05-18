<?php

namespace Drupal\masked_output\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'email_mailto' formatter.
 *
 * @FieldFormatter(
 *   id = "masked_email_output",
 *   label = @Translation("Mask Output"),
 *   field_types = {
 *     "email"
 *   }
 * )
 */
class MaskEmailOutputFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'mask_symbol' => '*',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['mask_symbol'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Masker'),
      '#description' => $this->t('Special character used to replace the characters. (Use special characters only, accepts only one value)'),
      '#default_value' => $this->getSetting('mask_symbol'),
      '#size' => 3,
      '#maxlength' => 1,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Masker: @mask_symbol', ['@mask_symbol' => $this->getSetting('mask_symbol')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $view_value = $this->viewValue($item);
      $elements[$delta] = $view_value;
    }
    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The textual output generated as a render array.
   */
  protected function viewValue(FieldItemInterface $item) {
    $mask_symbol = $this->getSetting('mask_symbol');
    $mail_part = explode("@", $item->value);
    $mail_part[0] = str_repeat($mask_symbol, strlen($mail_part[0]));
    $value = implode("@", $mail_part);
    return [
      '#type' => 'inline_template',
      '#template' => '{{ value|nl2br }}',
      '#context' => ['value' => $value],
    ];
  }
}
