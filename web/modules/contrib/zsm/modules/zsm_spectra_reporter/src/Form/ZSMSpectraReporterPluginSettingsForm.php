<?php
/**
 * @file
 * Contains \Drupal\zsm_spectra_reporter\Form\ZSMSpectraReporterPluginSettingsForm.
 */

namespace Drupal\zsm_spectra_reporter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ZSMSpectraReporterPluginSettingsForm.
 *
 * @package Drupal\zsm_spectra_reporter\Form
 *
 * @ingroup zsm_spectra_reporter
 */
class ZSMSpectraReporterPluginSettingsForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'zsm_spectra_reporter_settings';
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
        $form['zsm_spectra_reporter_settings']['#markup'] = 'Settings form for ZSM Spectra Reporter Plugin Settings. Manage field settings here.';
        return $form;
    }

}
