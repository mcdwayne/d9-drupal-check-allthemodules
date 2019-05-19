<?php
/**
 * @file
 * Contains \Drupal\zsm_mail_alert\Form\ZSMMailAlertPluginSettingsForm.
 */

namespace Drupal\zsm_mail_alert\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ZSMMailAlertPluginSettingsForm.
 *
 * @package Drupal\zsm_mail_alert\Form
 *
 * @ingroup zsm_mail_alert
 */
class ZSMMailAlertPluginSettingsForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'zsm_mail_alert_settings';
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
        $form['zsm_mail_alert_settings']['#markup'] = 'Settings form for ZSM MailAlert Plugin Settings. Manage field settings here.';
        return $form;
    }

}
