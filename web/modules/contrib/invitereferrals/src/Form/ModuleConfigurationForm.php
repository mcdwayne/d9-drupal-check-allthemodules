<?php

namespace Drupal\invitereferrals\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class ModuleConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invitereferrals_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'invitereferrals.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('invitereferrals.settings');
    $form['invitereferrals_enable_rewards'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Program'),
      '#default_value' => $config->get('invitereferrals_enable_rewards', TRUE),
      '#description' => $this->t('Enables the Referral program.'),
    ];

    $form['invitereferrals_secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Key'),
      '#default_value' => $config->get('invitereferrals_secret_key', ''),
      '#size' => 100,
      '#maxlength' => 100,
      '#description' => $this->t('You can find the Secret key in invitereferrals admin panel -> Developer Api -> API -> authentication. You may Click <a href="http://www.invitereferrals.com">here</a> to sign up on invitereferrals.'),
      '#required' => TRUE,
    ];

    $form['invitereferrals_brandID'] = [
      '#type' => 'textfield',
      '#title' => $this->t('brandID'),
      '#default_value' => $config->get('invitereferrals_brandID', ''),
      '#size' => 10,
      '#maxlength' => 10,
      '#description' => $this->t('You can find the brandID in invitereferrals admin panel -> Developer Api -> API -> authentication. You may Click <a href="http://www.invitereferrals.com">here</a> to sign up on invitereferrals.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('invitereferrals.settings');
    $config->set('invitereferrals_enable_rewards', $values['invitereferrals_enable_rewards']);
    $config->set('invitereferrals_secret_key', $values['invitereferrals_secret_key']);
    $config->set('invitereferrals_brandID', $values['invitereferrals_brandID']);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
