<?php

namespace Drupal\Digitallocker_requester\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

/**
 * Plugin implementation of the 'file_generic' widget.
 *
 * @FieldWidget(
 *   id = "file_digitallocker",
 *   label = @Translation("File + DigiLocker button"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class DigitalLockerFileWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'digilocker_enabled' => 1,
      'digilocker_exclusive' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element['digilocker_enabled'] = [
      '#type' => 'checkbox',
      '#weight' => 17,
      '#default_value' => $this->getSetting('digilocker_enabled'),
      '#title' => t('Enable Digital Locker on this field.'),
      '#description' => t('Enabling this option will show the digital locker upload button.'),
    ];

    $element['digilocker_exclusive'] = [
      '#type' => 'checkbox',
      '#weight' => 18,
      '#default_value' => $this->getSetting('digilocker_exclusive'),
      '#title' => t('Limit uploads exclusively to Digital Locker.'),
      '#description' => t('Enabling this option will hide the file upload and just retain the button.'),
    ];

    return parent::settingsForm($form, $form_state) + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      t('Progress indicator: @progress_indicator; Digital Locker enabled: @digilocker_enabled; Exclusive: @digilocker_exclusive', [
        '@progress_indicator' => $this->getSetting('progress_indicator'),
        '@digilocker_enabled' => $this->getSetting('digilocker_enabled') ? 'Yes' : 'No',
        '@digilocker_exclusive' => $this->getSetting('digilocker_exclusive') ? 'Yes' : 'No',
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if ($this->getSetting('digilocker_enabled')) {
      $element['digilocker'] = [
        '#weight' => $element['#weight'] + 1,
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['share_fm_dl'],
          'id' => str_replace('_', '-', $element['#field_name']) . '-' . $element['#delta'] . '-digilocker',
        ],
      ];
    }

    return $element;
  }

}
