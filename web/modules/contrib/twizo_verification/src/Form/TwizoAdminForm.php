<?php
/**
 * @file
 * Contains Drupal\twizo\Form\TwizoAdminForm
 */

namespace Drupal\twizo\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\twizo\Api\TwizoApi;
use Drupal\twizo\General\TwizoInfo;

class TwizoAdminForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'twizo.adminsettings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'twizo_admin_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('twizo.adminsettings');
        $twizoInfo = new TwizoInfo();
        $logo = ($config->get('widget_logo') !== '') ? parse_url($config->get('widget_logo')) : FALSE;

        if($logo['scheme'] == 'http' && $logo){
            drupal_set_message($this->t('Logo uses a HTTP protocol, HTTPS is recommended.'), 'error');
        }


        $form['twizo_api_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Twizo API key'),
            '#description' => $this->t('API keys can be found at <a href="@link">https://portal.twizo.com/applications/</a>.', ['@link' => 'https://portal.twizo.com/applications/']),
            '#required' => TRUE,
            '#default_value' => $config->get('twizo_api_key'),
        ];
        $form['twizo_api_server'] = [
            '#type' => 'select',
            '#title' => $this->t('Api server'),
            '#description' => $this->t('Select server location'),
            '#default_value' => $config->get('twizo_api_server'),
            '#options' => $twizoInfo->getApiServers(),
        ];
        $form['twizo_sender'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Default sender'),
            '#description' => $this->t('Set your default sender.'),
            '#default_value' => $config->get('twizo_sender'),
        ];
        $form['widget_logo'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Widget site logo path'),
            '#description' => $this->t('Website url to logo link (200x90px recommended).'),
            '#size' => 40,
            '#maxlength' => 512,
            '#default_value' => $config->get('widget_logo'),
        ];
        $form['twizo_enable_2fa'] = [
            '#type' => 'checkbox',
            '#title' => t('Enable two factor authentication'),
            '#default_value' => $config->get('twizo_enable_2fa'),
        ];
        if($config->get('twizo_enable_2fa')){
            $form['twizo_enable_backupcodes'] = [
                '#type' => 'checkbox',
                '#title' => t('Enable backup codes'),
                '#default_value' => $config->get('twizo_enable_backupcodes'),
            ];
        }

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);

        $twizoApi = new TwizoApi($form_state->getValue('twizo_api_key'), $form_state->getValue('twizo_api_server'));

        // Set error if api key is invalid.
        if(!$twizoApi->validateApiCredentials()) {
            $form_state->setError($form['twizo_api_key'], $this->t($twizoApi->getErrorMessage()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitForm($form, $form_state);

        $this->config('twizo.adminsettings')
            ->set('twizo_api_key', $form_state->getValue('twizo_api_key'))
            ->set('twizo_api_server', $form_state->getValue('twizo_api_server'))
            ->set('twizo_sender', $form_state->getValue('twizo_sender'))
            ->set('widget_logo', $form_state->getValue('widget_logo'))
            ->set('twizo_enable_2fa', $form_state->getValue('twizo_enable_2fa'))
            ->set('twizo_enable_backupcodes', $form_state->getValue('twizo_enable_backupcodes'))
            ->save();
    }
}