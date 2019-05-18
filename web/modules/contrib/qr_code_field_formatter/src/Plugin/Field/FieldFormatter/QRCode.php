<?php

namespace Drupal\qr_code_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plugin implementation of the QR Code formatter.
 *
 * @FieldFormatter(
 *   id = "qr_code",
 *   label = @Translation("QR Code"),
 *   field_types = {
 *     "string",
 *     "integer",
 *     "link",
 *     "email",
 *   }
 * )
 */
class QRCode extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    $summary[] = t('Converts a string to a QR Code.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Declare a setting named 'text_length', with
      // a default value of 'short'
      'version' => 'auto',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['version'] = [
      '#title' => t('QR Code Version'),
      '#type' => 'select',
      '#options' => [
        'auto' => $this->t('Auto'),
        '1' => $this->t('v. 1'),
        '2' => $this->t('v. 2'),
      ],
      '#default_value' => $this->getSetting('version'),
    ];

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $prefix = "";
      $field_type = $item->getPluginId();  //Is there a better function?
      if ($field_type == 'field_item:email') {
        $prefix = 'mailto:';
      }
      // Render each element as markup.
      $element[$delta] = [
        '#theme' => 'qr_code',
        '#uri' => '/QR/' . $prefix . $item->value,
      ];
    }

    return $element;
  }

}

