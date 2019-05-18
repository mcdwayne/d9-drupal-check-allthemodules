<?php

namespace Drupal\adobe_dtm\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class for the Adobe Dynamic Tag Management settings.
 */
class AdobeDynamicTagManagementSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adobe_dtm_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['adobe_dtm.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('adobe_dtm.settings');

    $form['enabled'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable Adobe DTM'),
      '#description'   => $this->t('Will enable the Adobe Dynamic Tag Management script.'),
      '#default_value' => $config->get('enabled'),
    ];

    $form['property_embed_code'] = [
      '#type'   => 'fieldset',
      '#title'  => $this->t('Property embed code'),
      '#states' => $this->statesArray('enabled'),
    ];

    $form['property_embed_code']['property_embed_code_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('ID'),
      '#default_value' => $config->get('property_embed_code_id'),
      '#size'          => 40,
      '#maxlength'     => 150,
      '#required'      => TRUE,
    ];

    $form['property_embed_code']['property_embed_code_hash'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Hash'),
      '#default_value' => $config->get('property_embed_code_hash'),
      '#size'          => 40,
      '#maxlength'     => 150,
      '#required'      => TRUE,
    ];

    $form['datalayer_enabled'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable Adobe DTM Datalayer'),
      '#description'   => $this->t('Will enable the Adobe Dynamic Tag Management datalayer output.'),
      '#default_value' => $config->get('datalayer_enabled'),
      '#states'        => $this->statesArray('enabled'),
    ];

    $form['environment'] = [
      '#type'          => 'select',
      '#title'         => t('Environment'),
      '#options'       => array(
        'production' => t('Production'),
        'staging'    => t('Staging'),
      ),
      '#default_value' => $config->get('environment'),
      '#states'        => $this->statesArray('enabled'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Returns states array for a form element.
   *
   * @param string $form_element_name
   *   The name of the form element.
   *
   * @return array
   *   The states array.
   */
  protected function statesArray($form_element_name) {
    return [
      'invisible' => [
        ':input[name="' . $form_element_name . '"]' => ['checked' => FALSE],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $property_embed_code_id = trim($form_state->getValue('property_embed_code_id'));
    $property_embed_code_hash = trim($form_state->getValue('property_embed_code_hash'));
    $form_state->setValue('property_embed_code_id', $property_embed_code_id);
    $form_state->setValue('property_embed_code_hash', $property_embed_code_hash);

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('adobe_dtm.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('property_embed_code_id', $form_state->getValue('property_embed_code_id'))
      ->set('property_embed_code_hash', $form_state->getValue('property_embed_code_hash'))
      ->set('datalayer_enabled', $form_state->getValue('datalayer_enabled'))
      ->set('environment', $form_state->getValue('environment'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
