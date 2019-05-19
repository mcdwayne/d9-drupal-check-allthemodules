<?php

namespace Drupal\wf_encrypt\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Plugin\WebformElement\WebformElement;
use Drupal\webform\Utility\WebformTidy;
use Drupal\webform\WebformElementBase;
use Drupal\webform\WebformElementManager;

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
    return array(
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformElementEncrypt'],
      ],
      '#theme_wrappers' => ['form_element'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return;
  }

  /**
   * Processes element attributes.
   */
  public static function processWebformElementEncrypt(&$element, FormStateInterface $form_state, &$complete_form) {
    $config = \Drupal::service('config.factory')->get('wf.encrypt')->get('element.settings');
    $values = $form_state->getValues();
    $field_name = $values['key'];

    $encryption_options = \Drupal::service('encrypt.encryption_profile.manager')->getEncryptionProfileNamesAsOptions();

    if (count($encryption_options) > 0) {
      $element['element_encrypt']['encrypt'] = [
          '#type' => 'checkbox',
          '#title' => t('Encrypt this field\'s value'),
        //'#description' => t('!link to edit encryption settings.', array('!link' => l('Click here', 'admin/config/system/encrypt'))),
          '#default_value' => $config[$field_name]['encrypt'] ? $config[$field_name]['encrypt'] : 0,
      ];

      $element['element_encrypt']['encrypt_profile'] = [
          '#type' => 'select',
          '#title' => t('Select Encryption Profile'),
          '#options' => $encryption_options,
          '#default_value' => isset($config[$field_name]['encrypt_profile']) ? $config[$field_name]['encrypt_profile'] : NULL,
          '#states' => [
              'visible' => [
                  [':input[name="properties[encrypt]"]' => ['checked' => TRUE]],
              ]
          ]
      ];

      $element['#element_validate'] = [[get_called_class(), 'validateWebformElementEncrypt']];
    }
    else {
      $element['element_encrypt']['message'] = array(
        '#markup' => t('Please configure the encryption profile to enable encryption for the element.'),
      );
    }

    return $element;
  }

  /**
   * Validates element attributes.
   */
  public static function validateWebformElementEncrypt(&$element, FormStateInterface $form_state, &$complete_form) {
    $config = \Drupal::service('config.factory')->getEditable('webform.encrypt')->get('element.settings');
    $values = $form_state->getValues();

    $field_name = $values['key'];
    $config[$field_name] = array(
      'encrypt' => $values['encrypt'],
      'encrypt_profile' => $values['encrypt_profile'],
    );

    \Drupal::service('config.factory')->getEditable('webform.encrypt')->set('element.settings', $config)->save();
  }
}

