<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\site_env\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class EnvironmentSettingForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'environment_setting_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
          'environment.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        global $base_url;

        $config = $this->config('environment.settings');
        $form['sitenv_fieldset'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Environment Settings'),
        ];


        $form['sitenv_fieldset']['siteinstance'] = [
          '#type' => 'select',
          '#title' => t('Select Site Environment'),
          '#description' => t('In code use environment.settings. Read value by $config->get(\'siteinstance\')'),
          '#options' => array('local' => 'Local', 'dev' => 'Dev', 'test' => 'Test', 'live' => 'Live'),
          '#default_value' => !empty($config->get('siteinstance')) ? $config->get('siteinstance') : 'dev',
        ];
        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Save config.
        $this->config('environment.settings')
          ->set('siteinstance', $form_state->getValue('siteinstance'))
          ->save();

        parent::submitForm($form, $form_state);
    }

}
