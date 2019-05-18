<?php

/**
 * @file
 * Contains \Drupal\captcha\Form\CaptchaSettingsForm.
 */

namespace Drupal\lr_hostedpage\Form;

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
    return ['lr_hostedpage.settings'];
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
    $hd_config = $this->config('lr_hostedpage.settings');
    // Configuration of which forms to protect, with what challenge.
    $form['hosted'] = [
      '#type' => 'details',
      '#title' => $this->t('Hosted Page Settings'),
       '#open' => TRUE,
    ];
    
    $form['hosted']['lr_hosted_page_enable'] = [
    '#type' => 'radios',
    '#title' => t('Do you want to enable hosted page<a title="Choosing yes will redirect users to signup on hosted page"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
    '#default_value' => $hd_config->get('lr_hosted_page_enable') ?   $hd_config->get('lr_hosted_page_enable')  : 0,
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
   $config = \Drupal::config('lr_ciam.settings');
    $apiKey = $config->get('api_key'); 
    $apiSecret = $config->get('api_secret'); 
    if($apiKey == ''){
      $apiKey = '';
      $apiSecret = '';
    }
    
    module_load_include('inc', 'lr_ciam');
    $data = lr_ciam_get_authentication($apiKey, $apiSecret);   
    if (isset($data['status']) && $data['status'] != 'status') {
      drupal_set_message($data['message'], $data['status']);
      return FALSE;
    }
    parent::SubmitForm($form, $form_state);
    $this->config('lr_hostedpage.settings')
      ->set('lr_hosted_page_enable', $form_state->getValue('lr_hosted_page_enable'))
      ->save();

  }
}
