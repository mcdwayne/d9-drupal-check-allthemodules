<?php

namespace Drupal\a12_connect\Form;

use Drupal\a12_connect\Inc\A12Config;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\a12_connect\Inc\A12Connector;
use Drupal\a12_connect\Inc\A12ConnectorException;

class SettingsForm extends ConfigFormBase
{
    protected function getEditableConfigNames() {
        return [
            'a12_connect.settings',
        ];
    }

    public function getFormId(){
        return 'settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('a12_connect.settings');
        $form['details'] = array(
            '#type' => 'fieldset',
            '#title' => t('Connection Details'),
        );
        $form['details']['a12_markup'] = array(
            '#markup' => t('Your connection details can be found by logging into your account at <a href="https://services.axistwelve.com/user" target="_blank">www.axistwelve.com</a> and selecting \'Access Keys\' from the dashboard. If you don\'t yet have an account, click <a href="http://www.axistwelve.com/free-trial" target="_blank">here</a> to start a 30 day Free trial.'),
        );
        $form['details']['a12_identifier'] = array(
            '#type' => 'textfield',
            '#title' => t('A12 Find username'),
            '#default_value' => A12Config::getId(),
            '#description' => t('The Identifier of the access key you wish to use to connect your site to the Find service.'),
        );
        $form['details']['a12_key'] = array(
            '#type' => 'textfield',
            '#title' => t('A12 Find password'),
            '#default_value' => A12Config::getSecret(),
            '#description' => t('The Secret Key of the access key you wish to use to connect your site to the Find service.'),
        );

        $form['buttons']['delete'] = array(
            '#type' => 'submit',
            '#value' => t('Delete subscription information'),
            '#submit' => array('a12_connect_delete_submit'),
        );
        return parent::buildForm($form, $form_state);
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $key = trim($form_state->getValue('a12_identifier'));
        $secret = trim($form_state->getValue('a12_key'));

        global $base_url;
        $connector = new A12Connector($key, $secret);

        try {
            $result = $connector->authenticate();
        }
        catch (A12ConnectorException $e) {
            drupal_set_message($e->getMessage(), 'error');
//            $form_state->setError($form, "");
            return FALSE;
        }
        return $result;
//        parent::validateForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        A12Config::setId($form_state->getValue('a12_identifier'));
        A12Config::setSecret($form_state->getValue('a12_key'));
        drupal_set_message(t('Subscription details saved.'));

        parent::submitForm($form, $form_state);
    }
}