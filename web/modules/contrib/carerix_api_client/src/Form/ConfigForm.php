<?php
/**
 * @file
 * Contains \Drupal\carerix_api_client\Form\ConfigForm.
 */

namespace Drupal\carerix_api_client\Form;

use Drupal\Core\Form\ConfigFormBase;
use \Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure maintenance settings for this site.
 */
class ConfigForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormID() {
        return 'carerix_api_client_config';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('carerix_api_client.config');

        $form['endpoint'] = array(
            '#title' => t('Endpoint URL'),
            '#type' => 'textfield',
            '#default_value' => $config->get('endpoint'),
        );
        $form['system'] = array(
            '#title' => t('System'),
            '#type' => 'textfield',
            '#default_value' => $config->get('system'),
        );
        $form['token'] = array(
            '#title' => t('Token'),
            '#type' => 'textfield',
            '#default_value' => $config->get('token'),
        );
        $form['proxy'] = array(
            '#title' => t('Proxy URL'),
            '#description' => t('Set proxy (optional, can be used for HTTP traffic
      debugging or if your service is behind a firewall). E.g.
      \'localhost:8888\'. If you don\'t know what it is just ignore it.'),
            '#type' => 'textfield',
            '#default_value' => $config->get('proxy'),
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config('carerix_api_client.config')
            ->set('endpoint', $form_state->getValue('endpoint'))
            ->set('system', $form_state->getValue('system'))
            ->set('token', $form_state->getValue('token'))
            ->set('proxy', $form_state->getValue('proxy'))
            ->save();

        parent::submitForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return array('carerix_api_client.config');
    }

}
?>