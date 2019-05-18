<?php

/**
 * @file
 * Contains \Drupal\captcha\Form\CaptchaSettingsForm.
 */

namespace Drupal\sociallogin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Displays the sociallogin settings form.
 */
class SocialLoginSettingsForm extends ConfigFormBase {


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sociallogin.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'sociallogin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sociallogin.settings');
        $form['#attached']['library'][] = 'user/drupal.user.admin';
    // Configuration of which forms to protect, with what challenge.
    $form['lr_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('LoginRadius API Settings'),
      '#description' => $this->t("You need to first create LoginRadius Site at LoginRadius "),
      '#open' => TRUE,
    ];

    $form['lr_settings']['sso_site_name'] = [
      '#type' => 'textfield',       
      '#title' => t('LoginRadius Site Name'),
      '#default_value' => $config->get('sso_site_name'),
      '#weight' => -10,
      '#required' => TRUE,
      '#description' => t('You can find the Site Name into your LoginRadius user account'),
    ];
    
    $form['lr_settings']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('To activate the module, insert LoginRadius API Key ( <a href="http://ish.re/INI1">How to get it?</a> )'),
    ];

    $form['lr_settings']['api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Secret'),
      '#default_value' => $config->get('api_secret'),
      '#description' => $this->t('To activate the module, insert LoginRadius API Secret ( <a href="http://ish.re/INI1">How to get it?</a> )'),
    ];

    $form['lr_basic_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Social Login Basic Settings'),
    ];

    $form['lr_basic_settings']['username_option'] = [
      '#type' => 'radios',
      '#title' => t('How would you like username to be created? Select the username syntax as per your preference.'),
      '#options' => [
        0 => $this->t('Firstname Lastname [Ex: John Doe]'),
        1 => $this->t('Firstname-Lastname [Ex: John-Doe]'),
        2 => $this->t('Email Address [Ex: johndoe@abc.com]'),
      ],
      '#default_value' => $config->get('username_option'),
    ];

    $form['lr_basic_settings']['login_redirection'] = [
      '#type' => 'radios',
      '#title' => t('Redirection settings after login'),
      '#options' => [
        0 => $this->t('Redirect to same page of site'),
        1 => $this->t('Redirect to profile page of site'),
        2 => $this->t('Redirect to custom page of site (If you want user to be redirected to specific URL after login)'),
      ],
      '#default_value' => $config->get('login_redirection'),
    ];
    $form['lr_basic_settings']['login_redirection']['custom_login_url'] = [
      '#type' => 'textfield',
      '#weight' => 50,
      '#default_value' => $config->get('custom_login_url'),
    ];
    $form['lr_basic_settings']['register_redirection'] = [
      '#type' => 'radios',
      '#title' => t('Redirection settings after registration'),
      '#options' => [
        0 => $this->t('Redirect to same page of site'),
        1 => $this->t('Redirect to profile page of site'),
        2 => $this->t('Redirect to custom page of site (If you want user to be redirected to specific URL after registration)'),
      ],
      '#default_value' => $config->get('register_redirection'),
    ];
    $form['lr_basic_settings']['register_redirection']['custom_register_url'] = [
      '#type' => 'textfield',
      '#weight' => 50,
      '#default_value' => $config->get('custom_register_url'),
    ];
    $form['lr_linking_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Social Account Linking Settings'),   
    ];
    $form['lr_linking_settings']['enable_linking'] = [
      '#type' => 'radios',
      '#title' => t('Do you want to enable social account linking at user profile page?'),
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
      '#default_value' => $config->get('enable_linking'),
    ];

    $form['lr_linking_settings']['linking_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('What heading do you want to display to the users to link multiple social networks to one account?'),
      '#default_value' => $config->get('linking_text'),
      '#description' => $this->t('This text will be displayed just above social login add more identities interface.'),
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
    
    module_load_include('inc', 'sociallogin');
    $data = get_authentication($form_state->getValue('api_key'), $form_state->getValue('api_secret'));
    if (isset($data['status']) && $data['status'] != 'status') {
      drupal_set_message($data['message'], $data['status']);
      return FALSE;
    }
    parent::SubmitForm($form, $form_state);
    $this->config('sociallogin.settings')
      ->set('sso_site_name', $form_state->getValue('sso_site_name'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('api_secret', $form_state->getValue('api_secret'))
      ->set('username_option', $form_state->getValue('username_option'))
      ->set('login_redirection', $form_state->getValue('login_redirection'))
      ->set('register_redirection', $form_state->getValue('register_redirection'))
      ->set('custom_login_url', $form_state->getValue('custom_login_url'))
      ->set('custom_register_url', $form_state->getValue('custom_register_url'))
      ->set('enable_linking', $form_state->getValue('enable_linking'))
      ->set('linking_text', $form_state->getValue('linking_text'))
      ->save();
        if (count(\Drupal::moduleHandler()->getImplementations('add_extra_sociallogin_config_settings')) > 0) {
    // Call all modules that implement the hook, and let them make changes to $variables.
    $config_data = \Drupal::moduleHandler()->invokeAll('add_extra_sociallogin_config_settings');
   
  }

  if(isset($config_data) && is_array($config_data)){
  foreach ($config_data as $key => $value) {
  
  $this->config('sociallogin.settings')
  ->set($value, $form_state->getValue($value))
  ->save();  
  } 
}

    drupal_set_message(t('Social Login settings have been saved.'), 'status');
    //Clear page cache
    foreach (Cache::getBins() as $service_id => $cache_backend) {
      if ($service_id == 'dynamic_page_cache') {
        $cache_backend->deleteAll();
      }
    }

  }
}
