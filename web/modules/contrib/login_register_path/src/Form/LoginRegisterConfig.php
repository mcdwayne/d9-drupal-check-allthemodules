<?php

/**
 * @file
 * Contents \Drupal\login_register_path\Form\LoginRegisterConfig
 */

namespace Drupal\login_register_path\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *	Class LoginRegisterConfig
 *	@package Drupal\login_register_path\Form
 */
class LoginRegisterConfig extends ConfigFormBase {

	/**
	 *	{@inheritdoc}
	 */
	public function getFormId() {
		return 'login_register_path_settings_form';
	}

	/**
	 *	{@inheritdoc}
	 */
	protected function getEditableConfigNames() {
		return ['login_register_path.settings'];
	}

	/**
	 *	{@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {

		$settings = $this->config('login_register_path.settings');

		// General message time form settings.
		$form['login_register_path_settings'] = array(
			'#type' => 'details',
			'#title' => $this->t('Login Register Path Settings'),
			'#open' => TRUE,
		);

		$form['login_register_path_settings']['enable'] = array(
	  	'#type' => 'checkbox',
  		'#title' => t('Enable Path'),
  		'#description' => $this->t(''),
  		'#default_value' => $settings->get('enable')
    );

		$form['login_register_path_settings']['login_path'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Login Path'),
			'#description' => $this->t('Change user/login path. (Eg: /login)'),
			'#default_value' => $settings->get('login_path'),
		);

		$form['login_register_path_settings']['register_path'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Register Path'),
			'#description' => $this->t('Change user/register path. (Eg: /register)'),
			'#default_value' => $settings->get('register_path'),
		);

		$form['login_register_path_settings']['password_path'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Password Path'),
			'#description' => $this->t('Change user/password path. (Eg: /password)'),
			'#default_value' => $settings->get('password_path'),
		);

		$form['login_register_path_settings']['logout_path'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Logout Path'),
			'#description' => $this->t('Change user/logout path. (Eg: /logout)'),
			'#default_value' => $settings->get('logout_path'),
		);

		$form['login_register_path_settings']['profile_path'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('User Profile Path'),
			'#description' => $this->t('Change user/1 path. (Eg: /profile)'),
			'#default_value' => $settings->get('profile_path'),
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
		$this->config('login_register_path.settings')
			->set('enable', $form_state->getValue('enable'))
			->set('login_path', $form_state->getValue('login_path'))
			->set('register_path', $form_state->getValue('register_path'))
			->set('password_path', $form_state->getValue('password_path'))
			->set('logout_path', $form_state->getValue('logout_path'))
			->set('profile_path', $form_state->getValue('profile_path'))
			->save();
			drupal_set_message(t('Save login register path successfully'), 'status');
	}
}