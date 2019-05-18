<?php

namespace Drupal\messagebird\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'telephone_link' formatter.
 *
 * @FieldFormatter(
 *   id = "messagebird_string",
 *   label = @Translation("Plain text"),
 *   field_types = {
 *     "messagebird"
 *   }
 * )
 */
class MessageBirdStringFormatter extends StringFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'format' => 'international',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['format'] = array(
      '#type' => 'select',
      '#title' => $this->t('Display value as'),
      '#default_value' => $this->getSetting('format'),
      '#options' => array(
        'number' => $this->t('Number'),
        'value' => $this->t('e164'),
        'international' => $this->t('International'),
        'national' => $this->t('National'),
        'rfc3966' => $this->t('rfc3966'),
      ),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $format_setting = $this->getSetting('format');

    $formats = array(
      'number' => $this->t('Number'),
      'value' => $this->t('e164'),
      'international' => $this->t('International'),
      'national' => $this->t('National'),
      'rfc3966' => $this->t('rfc3966'),
    );

    $summary[] = $this->t('Display format: @format', array('@format' => $formats[$format_setting]));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    $format_setting = $this->getSetting('format');

    return [
      '#type' => 'inline_template',
      '#template' => '{{ value|nl2br }}',
      '#context' => ['value' => $item->$format_setting],
    ];
  }

}
