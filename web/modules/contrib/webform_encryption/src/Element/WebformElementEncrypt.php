<?php

namespace Drupal\webform_encryption\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;

/**
 * Provides a webform element for element attributes.
 *
 * @FormElement("webform_element_encrypt")
 */
class WebformElementEncrypt extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformElementEncrypt'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Processes element attributes.
   */
  public static function processWebformElementEncrypt(&$element, FormStateInterface $form_state, &$complete_form) {

    // Extract the webform id we passed in.
    $webform_id = $element['#webform']->id();

    $config = \Drupal::service('config.factory')
      ->get('webform.encryption')
      ->get('element.settings');
    $values = $form_state->getValues();
    $field_name = $values['key'];



    $encryption_options = \Drupal::service('encrypt.encryption_profile.manager')
      ->getEncryptionProfileNamesAsOptions();

    if (count($encryption_options) > 0) {
      $element['element_encrypt']['encrypt'] = [
        '#type' => 'checkbox',
        '#title' => t("Encrypt this field's value"),
        '#description' => t('<a href=":link">Click here</a> to edit encryption settings.', [
          ':link' => Url::fromRoute('entity.encryption_profile.collection')
            ->toString(),
        ]),
        '#default_value' => $config[$webform_id][$field_name]['encrypt'] ? $config[$webform_id][$field_name]['encrypt'] : 0,
      ];

      $element['element_encrypt']['encrypt_profile'] = [
        '#type' => 'select',
        '#title' => t('Select Encryption Profile'),
        '#options' => $encryption_options,
        '#default_value' => isset($config[$webform_id][$field_name]['encrypt_profile']) ? $config[$webform_id][$field_name]['encrypt_profile'] : NULL,
        '#states' => [
          'visible' => [
            [':input[name="properties[encrypt]"]' => ['checked' => TRUE]],
          ],
        ],
      ];

      $element['#element_validate'] = [
        [
          get_called_class(),
          'validateWebformElementEncrypt',
        ],
      ];
    }
    else {
      $element['element_encrypt']['message'] = [
        '#markup' => t('Please configure the encryption profile to enable encryption for the element.'),
      ];
    }

    return $element;
  }

  /**
   * Validates element attributes.
   */
  public static function validateWebformElementEncrypt(&$element, FormStateInterface $form_state, &$complete_form) {

    // Extract the webform id we passed in.
    $webform_id = $element['#webform']->id();

    $config = \Drupal::service('config.factory')
      ->getEditable('webform.encryption')
      ->get('element.settings');
    $values = $form_state->getValues();

    $field_name = $values['key'];

    $config[$webform_id][$field_name] = [
      'encrypt' => $values['encrypt'],
      'encrypt_profile' => $values['encrypt_profile'],
    ];

    \Drupal::service('config.factory')
      ->getEditable('webform.encryption')
      ->set('element.settings', $config)
      ->save();
  }

}
