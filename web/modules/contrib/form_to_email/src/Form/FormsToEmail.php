<?php
/**
 * @file
 * Contains \Drupal\forms_to_email\Form\FormsToEmail.
 */
 
namespace Drupal\forms_to_email\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

 
class FormsToEmail extends ConfigFormBase {
 
	/**
	* {@inheritdoc}.
	*/
	public function getFormId() {
		return 'snippets_admin_settings';
	}

	/**
	+   * {@inheritdoc}
	+   */
	protected function getEditableConfigNames() {
		return array('snippets.settings');
	}

	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
	    
	    $config = $this->config('snippets.settings');

	    $form['forms_to_email_general'] = array(
		    '#type' => 'fieldset',
		    '#title' => t('General settings'),
	    );
		$form['forms_to_email_general']['my_form_id'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Form ID'),
			'#required' => TRUE,
		);
		$form['forms_to_email_general']['from'] = array(
			'#type' => 'email',
			'#title' => $this->t('From'),
			'#required' => TRUE,
		);
		$form['forms_to_email_general']['to'] = array(
			'#type' => 'email',
			'#title' => $this->t('Email To'),
		);
		$form['forms_to_email_general']['subject'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Subject'),
			'#required' => TRUE,
		);
		$form['forms_to_email_general']['redirect_success'] = array(
			'#type' => 'textarea',
			'#title' => $this->t('Redirect in success'),
		);
	    $form['forms_to_email_general']['disable_submit'] = array(
			'#type' => 'select',
			'#title' => $this->t('Disable other submits'),
			'#options' => array(
		        'yes' => t('Yes'),
		        'no' => t('No'),
      		),
		);
	    $form['forms_to_email_general']['ignore_fields'] = array(
			'#type' => 'textarea',
			'#title' => $this->t('Fields to Ignore'),
		);

		$form['actions']['#type'] = 'actions';
	    $form['actions']['submit'] = array(
	      '#type' => 'submit',
	      '#value' => $this->t('Save'),
	      '#button_type' => 'primary',
	    );

		return parent::buildForm($form, $form_state);;
	}

	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		
		$mailManager = \Drupal::service('plugin.manager.mail');

		$module = 'forms_to_email';
		$key = 'forms_to_email_mail_send';
		$from = $form_state->getValue('from');
		$to = $form_state->getValue('to');
		$message['headers'] = array(
		 'content-type' => 'text/html',
		 'MIME-Version' => '1.0',
		 'reply-to' => $to,
		 );
		$message['subject'] = $form_state->getValue('subject');
		$langcode = \Drupal::currentUser()->getPreferredLangcode();
		$send = true;

		$result = $mailManager->mail($module, $key, $from, $to, 'en', $message, NULL, $send);		
		
		drupal_set_message(t('Your message has been sent.'));
		
	}		
}
