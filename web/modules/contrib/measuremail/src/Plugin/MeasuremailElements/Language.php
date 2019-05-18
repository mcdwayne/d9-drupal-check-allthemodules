<?php

namespace Drupal\measuremail\Plugin\MeasuremailElements;

use Drupal\Core\Form\FormStateInterface;
use Drupal\measuremail\ConfigurableMeasuremailElementBase;

/**
 * Provides a 'language' element.
 *
 * @MeasuremailElements(
 *   id = "language",
 *   label = @Translation("Language"),
 *   description = @Translation("Provides a form element to input a langcode."),
 *   category = @Translation("Basic elements"),
 * )
 */
class Language extends ConfigurableMeasuremailElementBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label' => '',
      'id' => '',
      'automatic' => TRUE,
      'default_value' => '',
      'required' => TRUE,
      'hidden' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => $this->configuration['label'],
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'textfield',
      '#title' => t('Measuremail field ID'),
      '#description' => t('Same ID as on Measuremail'),
      '#default_value' => $this->configuration['id'],
      '#required' => TRUE,
    ];
    $form['automatic'] = [
      '#type' => 'checkbox',
      '#title' => t('Filled automatically, based on current page language'),
      '#default_value' => $this->configuration['automatic'],
    ];
    $form['default_value'] = [
      '#type' => 'textfield',
      '#title' => t('Default value'),
      '#default_value' => $this->configuration['default_value'],
      '#states' => [
        'visible' => [
          ':input[name="data[automatic]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['required'] = [
      '#type' => 'checkbox',
      '#title' => t('Required'),
      '#default_value' => $this->configuration['required'],
    ];
    $form['hidden'] = [
      '#type' => 'checkbox',
      '#title' => t('Hidden'),
      '#default_value' => $this->configuration['hidden'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['label'] = $form_state->getValue('label');
    $this->configuration['id'] = $form_state->getValue('id');
    $this->configuration['default_value'] = $form_state->getValue('default_value');
    $this->configuration['required'] = $form_state->getValue('required');
    $this->configuration['hidden'] = $form_state->getValue('hidden');
    $this->configuration['automatic'] = $form_state->getValue('automatic');
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $field_configuration = $this->getConfiguration()['data'];
    $return = [];
    $default_value = $field_configuration['default_value'];

    if ($field_configuration['automatic']) {
      $default_value = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }

    if ($field_configuration['hidden']) {

      if (strtolower($field_configuration['id']) == 'culture') {
        switch ($default_value) {
          case 'fr':
            $language_code = 'fr-FR';
            break;
          case 'nl':
            $language_code = 'nl-NL';
            break;
          case 'de':
            $language_code = 'de-DE';
            break;
          default:
            $language_code = 'en-GB';
            break;
        }
      } else {
        $language_code = $default_value;
      }

      $return = [
        '#type' => 'hidden',
        '#value' => $language_code,
      ];
    }
    else {
      $return = [
        '#type' => $this->getPluginId(),
        '#title' => t($field_configuration['label']),
        '#default_value' => t($field_configuration['default_value']),
        '#required' => ($field_configuration['required']) ? TRUE : FALSE,
      ];
    }
    return $return;
  }
}
