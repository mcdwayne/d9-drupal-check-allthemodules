<?php

namespace Drupal\yac_referral\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ReferralConfigurationForm.
 *
 * @package Drupal\yac_referral\Form
 * @group yac_referral
 */
class ReferralConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['yac_referral.configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_referral_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('yac_referral.configuration');
    $form['confirm_msg'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Success message'),
      '#description' => $this->t('Confirmation message for affiliation process.'),
      '#default_value' => !empty($config->get('confirm_msg')) ? $config->get('confirm_msg') : $this->t('Registration complete!'),
      '#required' => TRUE,
    ];
    $form['already_member_msg'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Error message'),
      '#description' => $this->t('Say something to tell the user that is already a member of an affiliation program.'),
      '#default_value' => !empty($config->get('already_member_msg')) ? $config->get('already_member_msg') : $this->t('You are already a member'),
      '#required' => TRUE,
    ];
    $form['invalid_msg'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Invalid message'),
      '#description' => $this->t('Say something to tell the user that the code provided is not valid.'),
      '#default_value' => !empty($config->get('invalid_msg')) ? $config->get('invalid_msg') : $this->t('Invalid code provided!'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('yac_referral.configuration')
      ->set('confirm_msg', $form_state->getValue('confirm_msg'))
      ->set('already_member_msg', $form_state->getValue('already_member_msg'))
      ->set('invalid_msg', $form_state->getValue('invalid_msg'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fields = [
      $form_state->getValue('confirm_msg'),
      $form_state->getValue('already_member_msg'),
      $form_state->getValue('invalid_msg'),
    ];
    foreach ($fields as $field) {
      if (strlen($field) > 255) {
        $form_state->setErrorByName($field, $this->t('The message is too long.'));
      }
    }
  }

}
