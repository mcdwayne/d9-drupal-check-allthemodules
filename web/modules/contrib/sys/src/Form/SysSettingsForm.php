<?php

/**
 * @file
 * Contains \Drupal\sys\Form\SysSettingsForm.
 */

namespace Drupal\sys\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SysSettingsForm extends ConfigFormBase {

	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'sys_settings_form';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$config = $this->config('sys.settings');

		$form['get_notified'] = [
			'#type' => 'checkbox',
			'#title' => t('Sys notification.'),
			'#default_value' => $config->get('get_notified'),
			'#description' => $this->t("Check if you want to get email notification for server disk    and memory."),
		];

		$form['email_to'] = [
			'#type' => 'textarea',
			'#title' => $this->t("Email to"),
			'#default_value' => $config->get('email_to'),
			'#description' => $this->t("Enter multiple email address to get notification email. Please enter email address one by one without adding comma at the end."),
		];

		$form['email_from_address'] = [
			'#type' => 'email',
			'#title' => $this->t("E-mail from address"),
			'#default_value' => $config->get('email_from_address'),
			'#description' => $this->t("The e-mail address that all e-mails will be from."),
		];

		$form['message'] = [
			'#type' => 'textarea',
			'#title' => $this->t("Body"),
			'#default_value' => $config->get('message'),
			'#description' => $this->t("The text that will be using in the top of emails."),
		];

		$form['cron'] = [
			'#type' => 'select',
			'#title' => $this->t("Sys notification cron"),
			'#default_value' => $config->get('cron'),
			'#description' => $this->t("Check if you want to get email notification for Sys for schedule cron. By default it runs according to site predefined cron schedule."),
			'#options' => [
				'daily' => t('Daily'),
				'weekly' => t('Weekly'),
			],
		];

		return parent::buildForm($form, $form_state);
	}

	/**
	 * Checks form email_to & email fields.
	 *
	 * @param array $form
	 * @param \Drupal\Core\Form\FormStateInterface $form_state
	 */
	public function validateServerdiscspace(array &$form, FormStateInterface $form_state) {
		$email_to = explode("\n", $form_state->getValue('email_to'));

		foreach ($email_to as $key => $value) {
			if (!empty($value)) {
				if (FALSE == filter_var(trim($value), FILTER_VALIDATE_EMAIL)) {
					$form_state->setErrorByName('email_to', $this->t('Invalid email address -- @value', ['@value' => trim($value)]));
				}
			}
		}

		$email_from_address = trim($form_state->getValue('email_from_address'));
		if (empty($email_from_address)) {
			$form_state->setErrorByName('email_from_address', $this->t('Invalid email address -- @value', ['@value' => trim($email_from_address)]));
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		// Retrieve the configuration.
		$this->config('sys.settings')
			// Set the submitted configuration setting.
			->set('get_notified', $form_state->getValue('get_notified'))
			->set('email_to', $form_state->getValue('email_to'))
			->set('email_from_address', $form_state->getValue('email_from_address'))
			->set('message', $form_state->getValue('message'))
			->set('cron', $form_state->getValue('cron'))
			->save();

		parent::submitForm($form, $form_state);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getEditableConfigNames() {
		return ['sys.settings'];
	}

}