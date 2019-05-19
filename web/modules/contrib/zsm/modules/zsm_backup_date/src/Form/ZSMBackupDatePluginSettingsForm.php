<?php
/**
 * @file
 * Contains \Drupal\zsm_backup_date\Form\ZSMBackupDatePluginSettingsForm.
 */

namespace Drupal\zsm_backup_date\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ZSMBackup_datePluginSettingsForm.
 *
 * @package Drupal\zsm_backup_date\Form
 *
 * @ingroup zsm_backup_date
 */
class ZSMBackupDatePluginSettingsForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'zsm_backup_date_settings';
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
        $form['zsm_backup_date_settings']['#markup'] = 'Settings form for ZSM BackupDatePlugin Settings. Manage field settings here.';
        return $form;
    }

}
