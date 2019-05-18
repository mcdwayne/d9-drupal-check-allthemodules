<?php

/**
 * @file
 * Contains \Drupal\captcha\Form\CaptchaSettingsForm.
 */

namespace Drupal\hostedpage\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Displays the socialprofiledata settings form.
 */
class HostedPageSettingsForm extends ConfigFormBase {


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hostedpage.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'hostedpage_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hostedpage.settings');
    // Configuration of which forms to protect, with what challenge.
    $form['hosted'] = [
      '#type' => 'details',
      '#title' => $this->t('Hosted Page Settings'),
       '#open' => TRUE,
    ];
    
    $form['hosted']['lr_hosted_page_enable'] = [
    '#type' => 'radios',
    '#title' => t('Do you want to enable hosted page<a title="Choosing yes will redirect users to signup on hosted page"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
    '#default_value' => $config->get('lr_hosted_page_enable') ?   $config->get('lr_hosted_page_enable')  : 0,
    '#options' => array(
      1 => t('Yes'),
      0 => t('No'),
    ),
  ];
    // Submit button.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    ];

    return parent::buildForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
   $sl_config = \Drupal::config('sociallogin.settings');
    $apiKey = $sl_config->get('api_key'); 
    $apiSecret = $sl_config->get('api_secret'); 
    if($apiKey == ''){
      $apiKey = '';
      $apiSecret = '';
    }
    
    module_load_include('inc', 'sociallogin');
    $data = get_authentication($apiKey, $apiSecret);   
    if (isset($data['status']) && $data['status'] != 'status') {
      drupal_set_message($data['message'], $data['status']);
      return FALSE;
    }
    parent::SubmitForm($form, $form_state);
    $this->config('hostedpage.settings')
      ->set('lr_hosted_page_enable', $form_state->getValue('lr_hosted_page_enable'))
      ->save();

  }
}
