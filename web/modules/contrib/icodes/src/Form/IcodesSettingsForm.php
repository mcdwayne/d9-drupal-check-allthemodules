<?php

namespace Drupal\icodes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/*
 * Icodes settings form.
 */

class IcodesSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'icodes_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return array('icodes.settings');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('icodes.settings');

        $api_url = Url::fromUri('http://www.icodes.co.uk/webservices/index.php',
                array('attributes' => array('target' => '_blank')));

        $form['api_key'] = array(
            '#type' => 'textfield',
            '#title' => t('iCodes Subscription ID'),
            '#required' => TRUE,
            '#default_value' => $config->get('api_key'),
            '#description' => t('The Subscription ID for your iCodes account. Get your subscription key on the homepage of your @apilink.',
                array('@apilink' => \Drupal::l(t('iCodes Dashboard'), $api_url))),
        );

        $form['icodes_base_url'] = array(
            '#type' => 'select',
            '#options' => array(
                'http://webservices.icodes.co.uk/ws2.php' => 'UK',
                'http://webservices.icodes-us.com/ws2_us.php' => 'USA',
                'http://webservices.icodes-us.com/ws2_india.php' => 'India',
            ),
            '#title' => t('iCodes Subscription ID'),
            '#required' => TRUE,
            '#default_value' => $config->get('icodes_base_url'),
            '#description' => t('Base Url for feeds '),
        );


        $form['icodes_username'] = array(
            '#type' => 'textfield',
            '#title' => t('iCodes Username'),
            '#required' => TRUE,
            '#default_value' => $config->get('icodes_username'),
            '#description' => t('The Subscription Username for your iCodes account. Find your Username on the homepage of your @apilink.',
                array('@apilink' => \Drupal::l(t('iCodes Dashboard'), $api_url))),
        );

        $default = $config->get('process_directory');
        $form['process_directory'] = array(
            '#type' => 'textfield',
            '#title' => t('Icodes directory for the latest XML Files'),
            '#description' => t('Directory inside public files (with leading private:// or public://)'),
            '#default_value' => ($default != null) ? $default : "private://icodes/process",
        );

        $default = $config->get('external_mode');
        $form['external_mode'] = array(
            '#type' => 'checkbox',
            '#title' => t('External Mode'),
            '#description' => t('Turn this off if you do not want to request the XML files from the iCodes sever (you are getting them on your server by some other means or you just want to process the downloaded file)'),
            '#default_value' => ($default !== null) ? $default : true,
        );


        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->config('icodes.settings');
        $config
            ->set('api_key', $form_state->getValue('api_key'))
            ->set('icodes_username', $form_state->getValue('icodes_username'))
            ->set('batch_limit', $form_state->getValue('batch_limit'))
            ->set('icodes_base_url', $form_state->getValue('icodes_base_url'))
            ->set('external_mode', $form_state->getValue('external_mode'))
            ->set('process_directory',
                $form_state->getValue('process_directory'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}