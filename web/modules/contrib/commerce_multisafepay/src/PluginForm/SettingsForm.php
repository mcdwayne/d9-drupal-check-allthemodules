<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */
namespace Drupal\commerce_multisafepay\PluginForm;

use Drupal\commerce_multisafepay\API\Client;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class SettingsForm extends ConfigFormBase {
    /**
     * Get the formID
     *
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'commerce_multisafepay_admin_settings';
    }

    /**
     * Get the editable configuration names
     *
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'commerce_multisafepay.settings',
        ];
    }

    /**
     * Build the form that displays it in your browser
     *
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        //get the current data
        $config = $this->config('commerce_multisafepay.settings');

        //Plugin Status select field (Test or Live) & get the plugin status if user has selected it before
        $form['account_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Account type'),
            '#options' => [
                'test' => $this->t('Test'),
                'live' => $this->t('Live'),
            ],
            '#default_value' => $config->get('account_type')
        ];

        //API input field & get the api if user has filled it before
        $form['live_api_key'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Live Api Key'),
            '#default_value' => $config->get('live_api_key'),
        );

        //API input field & get the api if user has filled it before
        $form['test_api_key'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Test Api Key'),
            '#default_value' => $config->get('test_api_key'),
        );
        
        //Plugin seconds active field
        $form['seconds_active'] = [
            '#type' => 'number',
            '#title' => $this->t('Seconds active'),
            '#default_value' => $config->get('seconds_active'),
            '#description' => $this->t('Time an order stays active.'),
        ];

        //generate the form
        return parent::buildForm($form, $form_state);
    }

    /**
     * Behavior when you submit the form
     *
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $liveApiKey = $form_state->getValue('live_api_key');
        $testApiKey = $form_state->getValue('test_api_key');
        $accountType = $form_state->getValue('account_type');
        $secondsActive = $form_state->getValue('seconds_active');

        // Retrieve the configuration
        $this->configFactory->getEditable('commerce_multisafepay.settings')
            // Set the submitted configuration setting
            ->set('live_api_key', $liveApiKey)
            ->set('test_api_key', $testApiKey)
            ->set('account_type', $accountType)
            ->set('seconds_active', $secondsActive)
            ->save();

        parent::submitForm($form, $form_state);
    }

}