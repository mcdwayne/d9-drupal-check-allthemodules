<?php
/**
 * @file
 * Contains \Drupal\zsm_access_log\Form\ZSMAccessLogPluginSettingsForm.
 */

namespace Drupal\zsm_access_log\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ZSMAccessLogPluginSettingsForm.
 *
 * @package Drupal\zsm_access_log\Form
 *
 * @ingroup zsm_access_log
 */
class ZSMAccessLogPluginSettingsForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'zsm_access_log_settings';
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
        $form['zsm_access_log_settings']['#markup'] = 'Settings form for ZSM Access Log Plugin Settings. Manage field settings here.';
        return $form;
    }

}
