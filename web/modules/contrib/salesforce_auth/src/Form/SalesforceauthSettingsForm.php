<?php

/**
 * @file
 * Contains \Drupal\salesforce_auth\Form\SalesforceauthSettingsForm.
 */

namespace Drupal\salesforce_auth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Salesforceauth settings form.
 */
class SalesforceauthSettingsForm extends ConfigFormBase {
	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'salesforce_auth_settings_form';
	}

	/**
	* {@inheritdoc}
	* Implements hook_form()
	*
	* The callback function for settings up the form for Salesforce auth.
	*
	* @param $node
	* @param $form_state
	* @return
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		$config = $this->config('salesforce_auth.settings');
		$form = parent::buildForm($form, $form_state);

		$sf_consumer_key = $config->get('salesforce_consumer_key');
		$sf_consumer_secret = $config->get('salesforce_consumer_secret');
		$sf_callback_uri = $config->get('salesforce_callback_uri');
		$sf_login_uri = $config->get('salesforce_login_uri');
		
		$form['restapi'] = array(
			'#type' => 'details',
			'#title' => t('Salesforce Configuration'),
			'#open' => TRUE, //Controls the HTML5 'open' attribute. Defaults to FALSE.
		);
		$form['restapi']['salesforce_consumer_key'] = array(
			'#title' => t('Consumer key'),
			'#description' => t('Salesforce consumer key'),
			'#type' => 'textfield',
			'#required' => TRUE,
			'#default_value' => $sf_consumer_key,
			'#prefix' => '<div class="form-group">',
            '#suffix' => '</div>',
		);
		$form['restapi']['salesforce_consumer_secret'] = array(
			'#title' => t('Consumer secret'),
			'#description' => t('Salesforce consumer secret'),
			'#type' => 'textfield',
			'#required' => TRUE,
			'#default_value' => $sf_consumer_secret,
			'#prefix' => '<div class="form-group">',
            '#suffix' => '</div>',
		);
		$form['restapi']['salesforce_callback_uri'] = array(
			'#title' => t('Callback URI'),
			'#description' => t('Salesforce Callback URL - must start with https://'),
			'#type' => 'textfield',
			'#required' => TRUE,
			'#default_value' => $sf_callback_uri,
			'#prefix' => '<div class="form-group">',
            '#suffix' => '</div>',
		);
		$form['restapi']['salesforce_login_uri'] = array(
			'#title' => t('Login URI'),
			'#description' => t('Salesforce Login URI'),
			'#type' => 'textfield',
			'#required' => TRUE,
			'#default_value' => $sf_login_uri,
			'#prefix' => '<div class="form-group">',
            '#suffix' => '</div>',
		);
		return $form;
	}

	/**
	* {@inheritdoc}
	* Implements hook_validate()
	*
	* @param $form
	* @param $form_state
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
		$values = $form_state->getValues();
		
		// Validate xWeb username.
		if (preg_match('#^[a-zA-Z0-9]+$#', $values['salesforce_consumer_key'])) {
			$form_state->setErrorByName('salesforce_consumer_key', t('Invalid consumer key; must be alpha numeric with special characters.'));
		}

		// Validate xWeb password.
		if (!preg_match('/^[1-9][0-9]*$/', $values['salesforce_consumer_secret'])) {
			$form_state->setErrorByName('salesforce_consumer_secret', t('Invalid Password; must be minimum 5 characters.'));
		}
		
		// Validate callback url
		if (!preg_match('%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i', $values['salesforce_callback_uri'])) {
			$form_state->setErrorByName('salesforce_callback_uri', t('Invalid callback url, must be a complete url starting with https://'));
		}

		// Validate login url.
		if (!preg_match('%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i', $values['salesforce_login_uri'])) {
			$form_state->setErrorByName('salesforce_login_uri', t('Netforum Cache Secret key must be 16 or 20 characters.'));
		}

		return parent::validateForm($form, $form_state);
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		parent::submitForm($form, $form_state);

		$this->config('salesforce_auth.settings')
		->set('salesforce_consumer_key', $form_state->getValue('salesforce_consumer_key'))
		->set('salesforce_consumer_secret', $form_state->getValue('salesforce_consumer_secret'))
		->set('salesforce_callback_uri', $form_state->getValue('salesforce_callback_uri'))
		->set('salesforce_login_uri', $form_state->getValue('salesforce_login_uri'))
		->save();
	}

	/**
	* {@inheritdoc}
	*/
	protected function getEditableConfigNames() {
		return ['salesforce_auth.settings'];
	}
}
