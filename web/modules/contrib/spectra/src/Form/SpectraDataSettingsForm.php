<?php
/**
 * @file
 * Contains \Drupal\spectra\Form\SpectraDataSettingsForm.
 */

namespace Drupal\spectra\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SpectraDataSettingsForm.
 *
 * @package Drupal\spectra\Form
 *
 * @ingroup spectra
 */
class SpectraDataSettingsForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'spectra_data_settings';
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
        $form['spectra_data_settings']['#markup'] = 'Settings form for Spectra Data Settings. Manage field settings here.';
        return $form;
    }

}
