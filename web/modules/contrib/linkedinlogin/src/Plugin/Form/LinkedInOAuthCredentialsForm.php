<?php

namespace Drupal\linkedinlogin\Plugin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Settings form for Social API LinkedIn.
 */
class LinkedInOAuthCredentialsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkedin_oauth_login_admin_settings';
  }
  
  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames()  {
    return array(
      'linkedin_oauth_login.settings'
    );
  }
  
  /**
   * Build Admin Settings Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state)  {
    $config = $this->config('linkedin_oauth_login.settings');
        
    $form['linkedin_oauth_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('LinkedIn OAuth Settings'),
      '#open' => TRUE
    ];
    $form['linkedin_oauth_settings']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('LinkedIn OAuth ClientID'),
      '#required' => TRUE,
      '#default_value' => $config->get('client_id')
    ];
    $form['linkedin_oauth_settings']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('LinkedIn OAuth Client Secret'),
      '#required' => TRUE,
      '#default_value' => $config->get('client_secret')
    ];
    $form['linkedin_oauth_settings']['redirect_url']  = [
      '#type' => 'textfield',
      '#title' => $this->t('LinkedIn OAuth Redirect URL'),
      '#required' => TRUE,
      '#default_value' => $config->get('redirect_url'),
      '#description'=> $this->t('Redirect URL should be in the following format ex: https://example.com/linkedin_oauth_login'),
    ];
    return parent::buildForm($form, $form_state);
  }
  
  /**
   * Build Admin Submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state)  {
    $values = $form_state->getValues();
    $this->config('linkedin_oauth_login.settings')
      ->set('client_id', $values['client_id'])
      ->set('client_secret', $values['client_secret'])
      ->set('redirect_url', $values['redirect_url'])
      ->save();
    drupal_set_message($this->t('Configuration Updated'));       
  }

}