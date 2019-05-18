<?php

namespace Drupal\acobot\Form;

use Drupal\Core\Form\ConfigFormBase;  
use Drupal\Core\Form\FormStateInterface;  

class AcobotSettingsForm extends ConfigFormBase {  

  /**  
   * {@inheritdoc}  
   */  
  public function getFormId() {  
    return 'acobot_admin_form';  
  }  
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['acobot.settings'];
  }

  /**  
   * {@inheritdoc}  
   */  
  public function buildForm(array $form, FormStateInterface $form_state) { 
   
    $config = $this->config('acobot.settings');  
    $form['acobot_token'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('acobot_token'),
      '#title' => t('Installation Key'),
      '#description' => t('To get your installation key, log in to your Acobot
         account and choose "My web - Installation."
         <a href="@acobot-register" target="_blank">Sign up</a>
         if you haven\'t.',
         array('@acobot-register' => 'http://acobot.com/user/register')),
    );

 

    return parent::buildForm($form, $form_state);  
 }

   /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Let active plugins validate their settings.
    foreach ($this->configurableInstances as $instance) {
      $instance->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('acobot.settings');
    // Let active plugins save their settings.
    foreach ($this->configurableInstances as $instance) {
      $instance->submitConfigurationForm($form, $form_state);
    }

    $config->set('acobot_token', $form_state->getValue('acobot_token'));
    $config->save();
    drupal_flush_all_caches();
  }

}







