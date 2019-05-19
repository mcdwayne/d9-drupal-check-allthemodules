<?php

namespace Drupal\sms_websms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * 
 */
class smsWebSmsSettingsForm extends ConfigFormBase {

/**
 * 
 */	
  public function getFormId() {
    return 'sms_websms_Settings_Form';
  }
	
/**
 * 
 */
  protected function getEditableConfigNames() {
    return [
      'sms_websms.sms_websms_setting.settings',
    ];
   }
   
/**
 * 
 */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sms_websms.sms_websms_setting.settings');	
	$form['sms_websms_username'] = [
    '#type' => 'textfield',
    '#title' => $this->t('Username'),
	'#description'  =>$this->t('Your login on WEBSMS site. If you don\'t heve an accaunt - you need register on <a href ="http://websms.ru/r">websms site </a>'),
    '#default_value' => $config->get('sms_websms_username'),
	];

    $form['sms_websms_password'] = [
    '#type' => 'textfield',
    '#title' =>$this-> t('Password'),
	'#description'  =>$this->t('Your password on WEBSMS site. If you don\'t heve an accaunt - you need register on <a href ="http://websms.ru/r">websms site </a>'),
    '#default_value' => $config->get('sms_websms_password'),
     ];
	return parent::buildForm($form, $form_state);
  }
  
/**
 * 
 */
  public function validateForm(array &$form, FormStateInterface $form_state) {
	  
  }
  
/**
 * 
 */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	$values = $form_state->getValues();
    $this->config('sms_websms.sms_websms_setting.settings')
	->set('sms_websms_username', $values['sms_websms_username'])
	->set('sms_websms_password', $values['sms_websms_password'])
	->save();
	drupal_set_message($this-> t('settings have been saved'));
	}	
}