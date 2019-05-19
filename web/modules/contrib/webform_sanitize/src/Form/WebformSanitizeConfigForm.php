<?php

namespace Drupal\webform_sanitize\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\Webform;

/**
 * Class WebformSanitizeConfigForm.
 */
class WebformSanitizeConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->configFactory()->get('webform.webform_sanitize.settings');
    $webforms = \Drupal::entityQuery('webform')->execute();

    $form['description'] = [
      '#markup' => t('Use this form to configure Webform Sanitize. Once configured, use the button below or drush to sanitize Webform submissions.<br /><br /><b>Usage:</b><br /><pre>drush webform-sanitize [webform_id]</pre>'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $form['manual'] = [
      '#type' => 'submit',
      '#value' => t('Save and sanitize submissions'),
      '#submit' => [
        [$this, 'submitForm'],
        'webform_sanitize_trigger_sanitization',
      ],
    ];

    $form['settings'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    // For each webform:
    foreach ($webforms as $webform_id) {
      $webform_entity = Webform::load($webform_id);
      $form['settings'][$webform_id] = [
        '#type' => 'details',
        '#title' => $webform_entity->get('title'),
        '#tree' => TRUE,
      ];

      $form['settings'][$webform_id]['description'] = [
        '#markup' => t('Webform id - @id', ['@id' => $webform_id]),
        '#prefix' => '<h2>',
        '#suffix' => '</h2>',
      ];

      $form['settings'][$webform_id]['manual'] = [
        '#type' => 'submit',
        '#manually_submit' => $webform_id,
        '#value' => t('Save all and sanitize @id', ['@id' => $webform_id]),
      ];

      $elements = $webform_entity->getElementsDecodedAndFlattened();

      // For each element within the webform:
      foreach ($elements as $elementId => $element) {

        if (empty($element['#title'])) {
          continue;
        }
        if (in_array($element['#type'], $this->getExcludedWebformElementTypes())) {
          continue;
        }

        $form['settings'][$webform_id][$elementId] = [
          '#type' => 'container',
          '#tree' => TRUE,
        ];

        $form['settings'][$webform_id][$elementId]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => '<b>' . $elementId . '</b> ' . $element['#title'] . ' (' . $element['#type'] . ')',
          '#default_value' => !empty($config->get($webform_id)[$elementId]),
        ];

        $form['settings'][$webform_id][$elementId]['params'] = [
          '#type' => 'details',
          '#title' => $elementId . ' sanitization settings',
          '#open' => TRUE,
          '#tree' => TRUE,
          '#states' => [
            'visible' => [
              ':input[name="settings[' . $webform_id . '][' . $elementId . '][enabled]"]' => ['checked' => TRUE],
            ],
          ],
        ];

        $form['settings'][$webform_id][$elementId]['params']['sanitizer'] = [
          '#type' => 'select',
          '#title' => 'Sanitization type',
          '#options' => [
            'blank' => 'Set to empty',
            'lorem' => 'Lorem Ipsum text',
            'user_short' => 'User Specified (short)',
            'user_long' => 'User Specified (long)',
          ],
          '#default_value' => $config->get($webform_id . '.' . $elementId . '.sanitizer'),
        ];

        $form['settings'][$webform_id][$elementId]['params']['lorem'] = [
          '#type' => 'number',
          '#title' => 'Number of characters to generate',
          '#description' => t('Use about 100 characters for an average sentence. 500 for a paragraph.'),
          '#states' => [
            'visible' => [
              ':input[name="settings[' . $webform_id . '][' . $elementId . '][params][sanitizer]"]' => ['value' => 'lorem'],
            ],
            'required' => [
              ':input[name="settings[' . $webform_id . '][' . $elementId . '][params][sanitizer]"]' => ['value' => 'lorem'],
            ],
          ],
          '#default_value' => $config->get($webform_id . '.' . $elementId . '.lorem'),
        ];

        $form['settings'][$webform_id][$elementId]['params']['user_short'] = [
          '#type' => 'textfield',
          '#title' => 'Text to use',
          '#states' => [
            'visible' => [
              ':input[name="settings[' . $webform_id . '][' . $elementId . '][params][sanitizer]"]' => ['value' => 'user_short'],
            ],
            'required' => [
              ':input[name="settings[' . $webform_id . '][' . $elementId . '][params][sanitizer]"]' => ['value' => 'user_short'],
            ],
          ],
          '#default_value' => $config->get($webform_id . '.' . $elementId . '.user_short'),
        ];

        $form['settings'][$webform_id][$elementId]['params']['user_long'] = [
          '#type' => 'textarea',
          '#title' => 'Text to use',
          '#states' => [
            'visible' => [
              ':input[name="settings[' . $webform_id . '][' . $elementId . '][params][sanitizer]"]' => ['value' => 'user_long'],
            ],
            'required' => [
              ':input[name="settings[' . $webform_id . '][' . $elementId . '][params][sanitizer]"]' => ['value' => 'user_long'],
            ],
          ],
          '#default_value' => $config->get($webform_id . '.' . $elementId . '.user_long'),
        ];

      }

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webform.webform_sanitize.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_sanitize_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $settings_store = [];

    foreach ($values['settings'] as $webform_id => $element) {
      foreach ($element as $element_id => $details) {
        if ($element_id == 'manual' || $element_id == 'description') {
          continue;
        }
        if (!$details['enabled'] == TRUE) {
          continue;
        }
        $settings_store[$webform_id][$element_id] = $details['params'];
      }
    }
    $editibleConfig = $this->configFactory()->getEditable('webform.webform_sanitize.settings');
    $editibleConfig->initWithData($settings_store);
    $editibleConfig->save();
    parent::submitForm($form, $form_state);

    if (!empty($form_state->getTriggeringElement()['#manually_submit'])) {
      webform_sanitize_trigger_sanitization($form_state->getTriggeringElement()['#manually_submit']);
    }

  }

  /**
   * Method to get element types to skip from sanitization.
   *
   * Todo, add a hook here to allow devs to skip other types of element.
   */
  protected function getExcludedWebformElementTypes() {
    return [
      'webform_actions',
    ];
  }

}
