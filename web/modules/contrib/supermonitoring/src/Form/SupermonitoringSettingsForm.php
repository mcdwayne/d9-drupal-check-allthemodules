<?php

namespace Drupal\supermonitoring\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SupermonitoringSettingsForm.
 *
 * @package Drupal\supermonitoring\Form
 */
class SupermonitoringSettingsForm extends ConfigFormBase {
	
	/**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'supermonitoring_admin_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'supermonitoring.settings',
        ];
    }   

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $config = $this->config('supermonitoring.settings');
		
		$form['token'] = array(
			'#type' => 'textfield',
			'#title' => t('Authorization token'),
			'#size' => 100,
			'#description' => t('If you already have a subscription at www.supermonitoring.com, enter your token to integrate the service with Drupal panel.<br /><br />If you have not an account at www.supermonitoring.com yet, <a href="https://www.supermonitoring.com/?utm_source=Drupal&utm_medium=text&utm_campaign=plugin" target="_blank"><strong>sign up here</strong></a> for a 14-day free trial.'),
			'#default_value' => $config->get('supermonitoring.token'),
	    );

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $config = $this->config('supermonitoring.settings');

        $config->set('supermonitoring.token', $form_state->getValue('token'));
        
        $config->save();

        return parent::submitForm($form, $form_state);
    }
}
