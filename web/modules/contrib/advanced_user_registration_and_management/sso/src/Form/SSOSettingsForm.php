<?php

/**
 * @file
 * Contains \Drupal\captcha\Form\CaptchaSettingsForm.
 */

namespace Drupal\sso\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Displays the socialprofiledata settings form.
 */
class SSOSettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return ['sso.settings'];
    }

    /**
     * Implements \Drupal\Core\Form\FormInterface::getFormID().
     */
    public function getFormId() {
        return 'sso_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('sso.settings');
        // Configuration of which forms to protect, with what challenge.
        $form['sso'] = [
          '#type' => 'details',
          '#title' => $this->t('Single Sign On Settings'),
          '#open' => TRUE,
        ];
        $form['sso']['sso_enable'] = [
          '#type' => 'radios',
          '#title' => t('Do you want to enable Single sign on (SSO)?'),
          '#default_value' => $config->get('sso_enable') ? $config->get('sso_enable') : 0,
          '#options' => array(
            1 => t('Yes'),
            0 => t('No'),
          ),
        ];
        $form['sso']['sso_enable_secure_cookie'] = [
          '#type' => 'radios',
          '#title' => t('If you have enabled secure cookies and you web is running on HTTP then select yes.'),
          '#default_value' => $config->get('sso_enable_secure_cookie') ? $config->get('sso_enable_secure_cookie') : 0,
          '#options' => array(
            1 => t('Yes'),
            0 => t('No'),
          ),
        ];
        // Submit button.
        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => t('Save configuration'),
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $sl_config = \Drupal::config('sociallogin.settings');
        $apiKey = $sl_config->get('api_key');
        $apiSecret = $sl_config->get('api_secret');
        if ($apiKey == '') {
            $apiKey = '';
            $apiSecret = '';
        }

        module_load_include('inc', 'sociallogin');
        $data = get_authentication($apiKey, $apiSecret);
        if (isset($data['status']) && $data['status'] != 'status') {
            drupal_set_message($data['message'], $data['status']);
            return FALSE;
        }
        parent::SubmitForm($form, $form_state);
        $this->config('sso.settings')
            ->set('sso_enable', $form_state->getValue('sso_enable'))
            ->set('sso_enable_secure_cookie', $form_state->getValue('sso_enable_secure_cookie'))
            ->save();
    }

}
