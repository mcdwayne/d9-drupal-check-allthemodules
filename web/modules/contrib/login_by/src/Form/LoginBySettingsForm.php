<?php

/**
 * @file
 * Contents \Drupal\login_by\Form\LoginBySettingsForm
 */

namespace Drupal\login_by\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *	Class LoginBySettingsForm
 *	@package Drupal\login_by\Form
 */
class LoginBySettingsForm extends ConfigFormBase {

	/**
	 *	{@inheritdoc}
	 */
	public function getFormId() {
		return 'login_by_settings_form';
	}

	/**
	 *	{@inheritdoc}
	 */
	protected function getEditableConfigNames() {
		return ['login_by.settings'];
	}

	/**
	 *	{@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$settings = $this->config('login_by.settings');

		// General login_by form settings.
		$form['login_by_settings'] = array(
			'#type' => 'details',
			'#title' => $this->t('Login by'),
			'#open' => TRUE,
		);

		$form['login_by_settings']['login_by_enable'] = array(
  		'#type' => 'radios',
		  '#title' => $this->t('Login by enable'),
  		'#description' => $this->t('Login by enable to choose'),
		  '#options' => array(
		    0 => $this->t('Default (Username)'),
		    1 => $this->t('Both (Username or email)'),
		    2 => $this->t('Email'),
		  ),
  		'#default_value' => $settings->get('login_by_enable')
    );
		$form['login_by_config'] = array(
			'#type' => 'details',
			'#title' => $this->t('Login config'),
			'#open' => TRUE,
		);
    $form['login_by_config']['login_by_placeholder'] = array(
  		'#type' => 'checkbox',
		  '#title' => $this->t('Enable placeholder'),
  		'#description' => $this->t('If you checked show placeholder.'),
  		'#default_value' => $settings->get('login_by_placeholder')
    );

    $form['login_by_config']['login_by_autocomplete'] = array(
  		'#type' => 'checkbox',
		  '#title' => $this->t('Enable autocomplete off'),
  		'#description' => $this->t('If you checked autocomplete off.'),
  		'#default_value' => $settings->get('login_by_autocomplete')
    );

    $form['login_by_config']['login_by_view_password'] = array(
  		'#type' => 'checkbox',
		  '#title' => $this->t('Enable view password'),
  		'#description' => $this->t('If you checked view password.'),
  		'#default_value' => $settings->get('login_by_view_password')
    );

    $form['login_by_config']['login_by_login_page'] = array(
  		'#type' => 'checkbox',
		  '#title' => $this->t('Enable login page'),
  		'#description' => $this->t('If you checked view login page design.'),
  		'#default_value' => $settings->get('login_by_login_page')
    );

		$form['login_by_login_in'] = array(
			'#type' => 'details',
			'#title' => $this->t('Change Log in'),
			'#open' => TRUE,
		);
		$form['login_by_login_in']['login_by_log_in'] = array(
			'#type' => 'textfield',
			'#required' => TRUE,
			'#title' => $this->t('Button Label'),
			'#default_value' => $settings->get('login_by_log_in')
		);
		$form['login_by_login_in']['login_by_title'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Page Title'),
			'#default_value' => $settings->get('login_by_title')
		);

		return parent::buildForm($form, $form_state);
	}

	/**
	 *	{@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state) {

	}

	/**
	 *	{@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$this->config('login_by.settings')
			->set('login_by_enable', $form_state->getValue('login_by_enable'))
			->set('login_by_placeholder', $form_state->getValue('login_by_placeholder'))
			->set('login_by_autocomplete', $form_state->getValue('login_by_autocomplete'))
			->set('login_by_view_password', $form_state->getValue('login_by_view_password'))
			->set('login_by_login_page', $form_state->getValue('login_by_login_page'))
			->set('login_by_log_in', $form_state->getValue('login_by_log_in'))
			->set('login_by_title', $form_state->getValue('login_by_title'))
			->save();
			drupal_flush_all_caches();
			drupal_set_message(t('Save login by configuration.'), 'status');
	}
}