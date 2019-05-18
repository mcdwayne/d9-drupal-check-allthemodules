<?php

namespace Drupal\ajax_file_download_counter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures ajax_dlcount settings
 */
Class SettingsForm extends ConfigFormBase{

	/**
	 * {@inheritdoc}
	 */
	public function getFormId(){
		return 'ajax_dlcount_setings_form';
	}

	/**
	 * {@inhereitdoc}
	 */
	protected function getEditableConfigNames(){
		return [
      'ajax_dlcount.settings',
    ];
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state){
		$ajax_dlcount_config = $this->config('ajax_dlcount.settings');
	  $form['ajax_dlcount_keep_history'] = [
	    '#type' => 'number',
	    '#title' => $this->t('Hours to Keep History'),
	    '#min' => 1,
	    '#default_value' => $ajax_dlcount_config->get('ajax_dlcount_keep_history'),
	    '#description' => "The number of hours to keep a history of what IP downloaded each file, to prevent counting two downloads from the same IP address.  Leave blank for forever (not recommended for large sites).",
	    '#required' => FALSE,
	  ];

	  return parent::buildForm($form, $form_state);
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state){
		$values = $form_state->getValues();
		$this->config('ajax_dlcount.settings')
			->set('ajax_dlcount_keep_history', $values['ajax_dlcount_keep_history'])
		  ->save();

		parent::submitForm($form, $form_state);
	}
}