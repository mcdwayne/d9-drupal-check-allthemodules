<?php

/**
 * @file
 * Contains Drupal\animated_scroll_to\Form\AnimatedScrollToForm.
 */

namespace Drupal\animated_scroll_to\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class AnimatedScrollToForm extends ConfigFormBase {

	/**
	 * {@inheritdoc}
	 */
	protected function getEditableConfigNames() {
		return [
			'animated_scroll_to.settings',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'animated_scroll_to_settings';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$config = $this->config('animated_scroll_to.settings');

		$form['default_settings'] = [
			'#type' => 'fieldset',
			'#title' => $this->t('Default settings'),
		];

		// Create a field for the delay.
		$form['default_settings']['delay'] = [
			'#type' => 'number',
			'#title' => $this->t('Animation delay'),
			'#description' => $this->t('The delay (in milliseconds). The time to wait before start animating.'),
			'#default_value' => ($config->get('delay')) ? $config->get('delay') : 0,
		];

		// Create a field for the default speed in milliseconds.
		$form['default_settings']['default_speed'] = [
			'#type' => 'number',
			'#title' => $this->t('Default animation speed'),
			'#description' => $this->t('The default animation speed (in milliseconds) which is used if data-scroll-speed is not set on the element.'),
			'#default_value' => ($config->get('default_speed')) ? $config->get('default_speed') : 600,
		];

		// Create a field for the default pause in milliseconds.
		$form['default_settings']['default_pause'] = [
			'#type' => 'number',
			'#title' => $this->t('Default animation pause'),
			'#description' => $this->t('The default animation pause (in milliseconds) which is used if data-scroll-pause is not set on the element.'),
			'#default_value' => ($config->get('default_pause')) ? $config->get('default_pause') : 3000,
		];

		// Create a field for the default correction in pixels.
		$form['default_settings']['default_correction'] = [
			'#type' => 'number',
			'#title' => $this->t('Default scroll correction'),
			'#description' => $this->t('The default scroll correction (in pixels) which is used if data-scroll-correction is not set on the element.'),
			'#default_value' => ($config->get('default_correction')) ? $config->get('default_correction') : 0,
		];

		// Create a field for the default animation.
		$form['default_settings']['default_easing'] = [
			'#type' => 'select',
			'#title' => $this->t('Default animation easing'),
			'#description' => $this->t('The default animation easing which is used if data-scroll-easing is not set on the element.'),
			'#default_value' => ($config->get('default_easing')) ? $config->get('default_easing') : 'swing',
			'#options' => [
				'swing' => 'Swing',
				'linear' => 'Linear',
			],
		];

		$form['enabled_functionalities'] = [
			'#type' => 'fieldset',
			'#title' => $this->t('Enabled functionalities'),
		];

		// Create a field for the default animation.
		$form['enabled_functionalities']['on_page_load'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Enable scrolling for links that will trigger a page reload'),
			'#description' => Html::escape($this->t('The animated scroll effect will work for links like <a href="/node/1#anchor"> and <a href="https://example.com/node/1#anchor">')),
			'#default_value' => ($config->get('on_page_load')) ? $config->get('on_page_load') : 0,
		];

		// Create a field for the default animation.
		$form['enabled_functionalities']['in_page'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Enable scrolling for in-page links'),
			'#description' => Html::escape($this->t('The animated scroll effect will work for links like <a href="#anchor">')),
			'#default_value' => ($config->get('in_page') != NULL) ? $config->get('in_page') : 0,
		];

		return parent::buildForm($form, $form_state);
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		parent::submitForm($form, $form_state);

		// Get the clean form values (only the data of the fields in the buildForm).
		$form_values = $form_state->cleanValues()->getValues();

		// Loop through each field and set the value in the config.
		foreach ($form_values as $key => $value) {
			$this->config('animated_scroll_to.settings')
				->set($key, $form_state->getValue($key))
				->save();
		}
	}
}
