<?php

namespace Drupal\fixed_text_email_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'fixed_text_email' formatter.
 *
 * @FieldFormatter(
 *   id = "fixed_text_email",
 *   label = @Translation("Email with fixed text"),
 *   field_types = {
 *     "email"
 *   }
 * )
 */
class FixedTextEmail extends FormatterBase {

  /**
   * {@inheritdoc}
   */
 public static function defaultSettings() {
   return array(
     'email_text' => t('Email'),
   ) + parent::defaultSettings();
 }
  public function settingsForm(array $parentForm, FormStateInterface $form_state) {
    $parentForm = parent::settingsForm($parentForm, $form_state);
    $settings = $this->getSettings();

    $form['email_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email text'),
      '#default_value' => $settings['email_text'],
      '#required' => TRUE,
    ];

    return $form + $parentForm;
  }

  public function settingsSummary() {
    $settings = $this->getSettings();

    $summary[] = $this->t('Email text: @text', ['@text' => $settings['email_text']]);

    return $summary;
  }

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'link',
        '#title' => $settings['email_text'],
        '#url' => Url::fromUri('mailto:' . $item->value),
      ];
    }

    return $elements;
  }
}
