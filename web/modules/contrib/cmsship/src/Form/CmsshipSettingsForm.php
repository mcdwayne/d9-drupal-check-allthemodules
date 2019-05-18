<?php

namespace Drupal\cmsship\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CmsshipSettingsForm extends ConfigFormBase {
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'cmsship_admin_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'cmsship.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('cmsship.settings');

        $form['account'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Account to login too'),
            '#default_value' => $config->get('cmsship_account'),
            '#description' => t('This is the user id of the account you wish to login to. Go to the user page in the admin area and it will be in the URL after /user/.'),
            '#required' => TRUE
        );

        $form['site_key'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Site Key'),
            '#default_value' => $config->get('cmsship_key'),
            '#description' => t('This is the Site Key for that domain.'),
            '#required' => TRUE
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        // Validate API Token
        $client = \Drupal::httpClient();

        try {
            $request = $client->request('GET', 'https://cmsship.com/api/key', array(
                'headers' => array(
                    'X-cmsship-Token' => $form_state->getValue('site_key')
                )
            ));
        } catch(\Exception $e) {
            $form_state->setErrorByName('site_key', t('Site Key is not valid.'));

            parent::validateForm($form, $form_state);
            
            return;
        }

        if ($request->getStatusCode() != '200') {
            $form_state->setErrorByName('site_key', t('Site Key is not valid.'));
        }

        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = \Drupal::service('config.factory')->getEditable('cmsship.settings');

        $config->set('cmsship_account', $form_state->getValue('account'))
            ->save();

        $config->set('cmsship_key', $form_state->getValue('site_key'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
