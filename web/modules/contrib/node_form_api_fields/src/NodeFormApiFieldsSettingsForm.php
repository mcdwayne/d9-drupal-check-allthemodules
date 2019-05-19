<?php

namespace Drupal\node_form_api_fields;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure hello settings for this site.
 */
class NodeFormApiFieldsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_form_api_fields_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'node_form_api_fields.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('node_form_api_fields.settings');

    $form['wrap_in_fieldset'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wrap in Fieldset'),
      '#default_value' => $config->get('wrap_in_fieldset'),
      '#description' => $this->t('Check this box to wrap all fields created 
        using the hook_node_form_api_fields_form_alter() in a fieldset.'),
    ];

    $form['fieldset_wrapper_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fieldset Wrapper Label'),
      '#default_value' => $config->get('fieldset_wrapper_label'),
      '#description' => $this->t('Set the text in the legend of the wrapper 
        fieldset to something other than the default "Additional Fields".'),
      '#states' => [
        'visible' => [
          ':input[name="wrap_in_fieldset"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['fieldset_default_weight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fieldset Default Weight'),
      '#default_value' => $config->get('fieldset_default_weight'),
      '#maxlength' => 10,
      '#description' => $this->t('Set the default weight for the fieldset to
        set its initial position in the form. You could override this on a
        specific form by changing the $form[\'extra_fields\'][\'#weight\']'),
      '#states' => [
        'visible' => [
          ':input[name="wrap_in_fieldset"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $wrap = $form_state->getValue('wrap_in_fieldset');
    $weight = $form_state->getValue('fieldset_default_weight');
    if ($wrap && $weight) {
      if (!is_numeric($weight)) {
        $message = $this->t('Please enter a valid number for the weight.');
        $form_state->setErrorByName('fieldset_default_weight', $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('node_form_api_fields.settings')
      ->set('wrap_in_fieldset', $form_state->getValue('wrap_in_fieldset'))
      ->set('fieldset_wrapper_label', $form_state->getValue('fieldset_wrapper_label'))
      ->set('fieldset_default_weight', $form_state->getValue('fieldset_default_weight'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
