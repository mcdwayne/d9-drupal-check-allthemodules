<?php
/**
 * @file
 * Contains \Drupal\spectra\Form\SpectraStatementSettingsForm.
 */

namespace Drupal\spectra\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SpectraStatementSettingsForm.
 *
 * @package Drupal\spectra\Form
 *
 * @ingroup spectra
 */
class SpectraStatementSettingsForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'spectra_statement_settings';
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
        $form['spectra_statement_settings']['#markup'] = 'Settings form for Spectra Statement Settings. Manage field settings here.';
        return $form;
    }

}
