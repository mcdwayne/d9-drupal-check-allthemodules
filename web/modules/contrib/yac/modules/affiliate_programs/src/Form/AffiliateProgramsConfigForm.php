<?php

namespace Drupal\yac_affiliate_programs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AffiliateProgramsConfigForm.
 *
 * @package Drupal\yac_affiliate_programs\Form
 * @group yac_affiliate_programs
 */
class AffiliateProgramsConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['yac_affiliate_programs.configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_affiliate_programs_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('yac_affiliate_programs.configuration');
    $form['include_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include admin?'),
      '#description' => $this->t('Check if you want to include admin in affiliation programs.'),
      '#default_value' => !empty($config->get('include_admin')) ? $config->get('include_admin') : FALSE,
      '#required' => FALSE,
    ];
    $form['is_multilevel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do you want to enable multilevel?'),
      '#description' => $this->t('Enable multilevel programs and create complex affiliation programs.'),
      '#default_value' => !empty($config->get('is_multilevel')) ? $config->get('is_multilevel') : FALSE,
      '#required' => FALSE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('yac_affiliate_programs.configuration')
      ->set('include_admin', $form_state->getValue('include_admin'))
      ->set('is_multilevel', $form_state->getValue('is_multilevel'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

}
