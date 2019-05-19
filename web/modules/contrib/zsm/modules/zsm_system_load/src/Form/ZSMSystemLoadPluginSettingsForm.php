<?php
/**
 * @file
 * Contains \Drupal\zsm_system_load\Form\ZSMSystemLoadPluginSettingsForm.
 */

namespace Drupal\zsm_system_load\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ZSMSystemLoadPluginSettingsForm.
 *
 * @package Drupal\zsm_system_load\Form
 *
 * @ingroup zsm_system_load
 */
class ZSMSystemLoadPluginSettingsForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'zsm_system_load_settings';
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
        $form['zsm_system_load_settings']['#markup'] = 'Settings form for ZSM SystemLoadPlugin Settings. Manage field settings here.';
        return $form;
    }

}
