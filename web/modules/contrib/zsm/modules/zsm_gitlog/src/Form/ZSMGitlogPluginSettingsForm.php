<?php
/**
 * @file
 * Contains \Drupal\zsm_gitlog\Form\ZSMGitlogPluginSettingsForm.
 */

namespace Drupal\zsm_gitlog\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ZSMGitlogPluginSettingsForm.
 *
 * @package Drupal\zsm_gitlog\Form
 *
 * @ingroup zsm_gitlog
 */
class ZSMGitlogPluginSettingsForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'zsm_gitlog_settings';
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
        $form['zsm_gitlog_settings']['#markup'] = 'Settings form for ZSM Gitlog Plugin Settings. Manage field settings here.';
        return $form;
    }

}
