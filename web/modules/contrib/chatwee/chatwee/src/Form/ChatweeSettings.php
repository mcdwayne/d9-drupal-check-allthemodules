<?php

/**
 * @file
 * Contains \Drupal\chatwee\Form\ChatweeSettings.
 */

namespace Drupal\chatwee\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class ChatweeSettings extends ConfigFormBase {

	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'chatwee_settings';
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		drupal_flush_all_caches();

		$config = $this->config('chatwee.settings');

		foreach (Element::children($form) as $variable) {
			$config->set($variable, $form_state->getValue($form[$variable]['#parents']));
		}
		$config->save();

		if(method_exists($this, '_submitForm')) {
			$this->_submitForm($form, $form_state);
		}

		parent::submitForm($form, $form_state);
	}

	/**
	* {@inheritdoc}
	*/
	protected function getEditableConfigNames() {
		return ['chatwee.settings'];
	}

	public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

		$settings = \Drupal::config('chatwee.settings');

		$form['chatwee_enable'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Enable Chatwee module'),
			'#default_value' => $settings->get('chatwee_enable')
		];
		$form['chatwee_code'] = [
			'#type' => 'textarea',
			'#title' => $this->t('Chatwee code'),
			"#description" => t("Copy the Chatwee installation code from your <a href='https://client.chatwee.com/v2/dashboard' target='_blank'>Dashboard</a> and paste it into the box. If you don't have a Chatwee account yet, please <a href='https://client.chatwee.com/register-form' target='_blank'>sign up</a> absolutely for free."),
			'#default_value' => $settings->get('chatwee_code')
		];
		$form['chatwee_disable_offline_users'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Show only for logged-in users'),
			'#default_value' => $settings->get('chatwee_disable_offline_users')
		];
		$form['chatwee_enable_sso'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Enable SSO login'),
			'#default_value' => $settings->get('chatwee_enable_sso'),
			"#description" => $this->t("Check this box if you want your users to log in via Single Sign-on.")
		];
		$form['chatwee_chat_id'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Chat ID'),
			'#default_value' => $settings->get('chatwee_chat_id'),
			"#description" => $this->t("Enter your Chat ID available in the Chatwee Client Panel <a href='https://client.chatwee.com/v2/integration' target='_blank'>Integration</a> page.")
		];
		$form['chatwee_client_key'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Client Key'),
			'#default_value' => $settings->get('chatwee_client_key'),
			"#description" => $this->t("Enter your API Key available in the Chatwee Client Panel <a href='https://client.chatwee.com/v2/integration' target='_blank'>Integration</a> page.")
		];

		return parent::buildForm($form, $form_state);
	}
}
