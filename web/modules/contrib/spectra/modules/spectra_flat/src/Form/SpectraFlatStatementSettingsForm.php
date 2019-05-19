<?php
/**
 * @file
 * Contains \Drupal\spectra_flat\Form\SpectraFlatStatementSettingsForm.
 */

namespace Drupal\spectra_flat\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SpectraFlatStatementSettingsForm.
 *
 * @package Drupal\spectra_flat\Form
 *
 * @ingroup spectra_flat
 */
class SpectraFlatStatementSettingsForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'spectra_flat_statement_settings';
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
        $form['spectra_flat_statement_settings']['#markup'] = 'Settings form for Spectra Flat Statement Settings. Manage field settings here.';
        return $form;
    }

}
