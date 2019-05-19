<?php
/**
 * @file
 * Contains \Drupal\zsm_haproxy\Form\ZSMHAProxyPluginSettingsForm.
 */

namespace Drupal\zsm_haproxy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ZSMHAProxyPluginSettingsForm.
 *
 * @package Drupal\zsm_haproxy\Form
 *
 * @ingroup zsm_haproxy
 */
class ZSMHAProxyPluginSettingsForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'zsm_haproxy_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Empty implementation of the abstract submit class.
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['zsm_haproxy_settings']['#markup'] = 'Settings form for ZSM HAProxyPlugin Settings. Manage field settings here.';
        return $form;
    }

}
