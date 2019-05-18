<?php
/**  
 * @file  
 * Contains Drupal\mailme\Form\MailAdmin.  
 */ 
namespace Drupal\mailme\Form;

use Drupal\Core\Form\ConfigFormBase;  
use Drupal\Core\Form\FormStateInterface;  

class MailAdmin extends ConfigFormBase {  
	/**  
	* {@inheritdoc}  
	*/  
	protected function getEditableConfigNames() {  
		return [  
			'mailme.adminsettings',  
		];  
	}  
	/**  
	* {@inheritdoc}  
	*/  	
	public function getFormId() {  
		return 'mailme_admin_form';  
	} 	
	/**  
	* {@inheritdoc}  
	*/  	
	
	// One field on the form for the email.
	public function buildForm(array $form, FormStateInterface $form_state) {  
		$config = $this->config('mailme.settings');  

		$form['mailme_email'] = [  
			'#type' => 'textfield',  
			'#title' => $this->t('Send-to Email'),  
			'#description' => $this->t('Contact form should send an email to this address'),  
			'#default_value' => $config->get('mailme_email'),  
		];  

		return parent::buildForm($form, $form_state);  
  }
	/**  
	* {@inheritdoc}  
	*/  
	
	//Save the value of the email address entered.
	public function submitForm(array &$form, FormStateInterface $form_state) {  
		parent::submitForm($form, $form_state);  

		$config = \Drupal::configFactory()->getEditable('mailme.settings')
			->set('mailme_email', $form_state->getValue('mailme_email'))  
			->save();  
	} 	
}
