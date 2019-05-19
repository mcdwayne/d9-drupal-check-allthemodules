<?php

namespace Drupal\ssp_idp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SimpleSAMLphpIDPForm.
 *
 * @package Drupal\ssp_idp\Form
 */
class SSPIDPForm extends ConfigFormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ssp_idp_form';
  }
  
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ssp_idp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ssp_idp.settings');
    $form['ssp_idp_samlfolder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SimpleSAMLphp folder'),
      '#description' => $this->t('Enter location of SimpleSAMLphp installation.'),
      '#default_value' => $config->get('ssp_idp_samlfolder'),
      '#maxlength' => 255,
      '#size' => 64,
    ];
    $form['ssp_idp_auth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authentication method name'),
      '#description' => $this->t('Enter the authentication method name.'),
      '#default_value' => $config->get('ssp_idp_auth'),
      '#maxlength' => 255,
      '#size' => 64,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ssp_idp.settings')
      ->set('ssp_idp_samlfolder', $form_state->getValue('ssp_idp_samlfolder'))
      ->set('ssp_idp_auth', $form_state->getValue('ssp_idp_auth'))
      ->save();
    return parent::submitForm($form, $form_state);

  }

}
