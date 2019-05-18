<?php

namespace Drupal\corporatelogin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use  \Drupal\user\Entity\User;

/**
 * Class HelloWorldCustomForm.
 */
class CorporateLoginAdminForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'corporatelogin_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
	
	
  $form['corporate_account_management_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Corporate Account Management'),
    '#collapsible' => TRUE,
  );
  $form['corporate_account_management_settings']['corporate_account_admin_email'] =
  array(
    '#type' => 'select',
    '#title' => t('Domain Name'),
    '#description' => t('Choose the domain name which needs to be a Corporate Account'),
    '#options' => corporate_account_admin_email_options(),
    '#required' => TRUE,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save Configuration'),
    '#attributes' => array('class' => array('btn', 'continue', 'proceed')),
  );

    return $form;	
  }

 /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  
  $values = $form_state->getValues();
  $hasEmail = db_select('corporate_login_details', 'n')->fields('n')->condition('email', $values['corporate_account_admin_email'], '=')->execute()->fetchAssoc();
 
		
		if (empty($hasEmail)) {
			return TRUE;
		}
		else{
			$form_state->setErrorByName('corporate_account_admin_email', t('Record already Exist!'));			
		}
		
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
		$values = $form_state->getValues();
		$insert = db_insert('corporate_login_details')
		-> fields(array(
			'email' => $values['corporate_account_admin_email'],
		))
		->execute();
	
	drupal_set_message(t('Settings have been saved'));
	
	

 

		

  }
}
/**
 * Corporate Account configuration - Fetch email from database.
 */
function corporate_account_admin_email_options() {
	$user_storage = \Drupal::service('entity_type.manager')->getStorage('user');
	$ids = $user_storage->getQuery()
	  ->condition('status', 1)
	  ->condition('roles', 'corporate')
	  ->execute();
	  $options = array();
	  $options[NULL] = "- Any -";
	foreach ($ids as $id){	
		$account = \Drupal\user\Entity\User::load($id); // pass your uid
		$caEmailID = $account->getEmail();
		$caEmailID_split = explode('@', $caEmailID);
		$caUserDomain = $caEmailID_split[1];
		$options[$caUserDomain] = $caUserDomain;	 
	}
	return $options;
}