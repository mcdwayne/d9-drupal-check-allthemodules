<?php

namespace Drupal\drupaneo\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupaneo\Service\AkeneoService;

/**
 * Drupaneo settings form.
 */
class SettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'drupaneo_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'drupaneo.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('drupaneo.settings');

        $form['url'] = array(
            '#title' => $this->t('Akeneo URL'),
            '#type' => 'textfield',
            '#default_value' => $config->get('url'),
        );

        $form['user'] = array(
            '#title' => $this->t('User'),
            '#type' => 'details',
            '#open' => empty($config->get('username')) || empty($config->get('password')),
            'username' => array(
                '#title' => $this->t('User name'),
                '#type' => 'textfield',
                '#default_value' => $config->get('username'),
            ),
            'password' => array(
                '#title' => $this->t('Password'),
                '#type' => 'password',
                '#attributes' => array('value' => $config->get('password')),
            ),
        );

        $form['api'] = array(
            '#title' => $this->t('Credentials'),
            '#type' => 'details',
            '#open' => empty($config->get('client_id')) || empty($config->get('client_secret')),
            'client_id' => array(
                '#title' => $this->t('Client Id'),
                '#type' => 'textfield',
                '#default_value' => $config->get('client_id'),
            ),
            'client_secret' => array(
                '#title' => $this->t('Client secret'),
                '#type' => 'textfield',
                '#default_value' => $config->get('client_secret'),
            ),
        );

        /**
         * @var AkeneoService $akeneo
         */
        $akeneo = \Drupal::service('drupaneo.akeneo');

        $channels = array('' => t('All'));

        try {
            $result = $akeneo->getChannels(1, 100, 'false');
            if (isset($result->_embedded) && isset($result->_embedded->items)) {
                foreach ($result->_embedded->items as $channel) {
                    $channels[$channel->code] = $channel->labels->en_US;
                }
            }
            $form['advanced'] = array(
                '#title' => $this->t('Advanced Options'),
                '#type' => 'details',
                '#open' => true,
                'scope' => array(
                    '#title' => $this->t('Channel'),
                    '#type' => 'select',
                    '#description' => 'Product and attributes scope',
                    '#options' => $channels,
                    '#default_value' => $config->get('scope'),
                ),
                'completeness' => array(
                    '#title' => $this->t('Completeness'),
                    '#type' => 'number',
                    '#description' => 'Minimum required completeness (scope selection needed)',
                    '#min' => 0,
                    '#max' => 100,
                    '#default_value' => empty($config->get('completeness'))?0:$config->get('completeness'),
                ),
            );
        }
        catch(\Exception $e) {
            $config->set('scope', '');
        }
        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('drupaneo.settings');

        $values = $form_state->getValues();
        $config->set('url', $values['url']);
        $config->set('username', $values['username']);
        $config->set('password', $values['password']);
        $config->set('client_id', $values['client_id']);
        $config->set('client_secret', $values['client_secret']);
        $config->set('scope', $values['scope']);
        $config->set('completeness', $values['completeness']);
        $config->save();

        parent::submitForm($form, $form_state);
    }

}
