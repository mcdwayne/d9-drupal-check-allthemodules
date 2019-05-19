<?php
/**
 * @file
 * Contains \Drupal\spectra\Form\SpectraContentForm.
 */

namespace Drupal\spectra\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SpectraContentForm.
 *
 * @package Drupal\spectra\Form
 *
 * @ingroup spectra
 */
class SpectraContentForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'spectra_content';
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
        $form['spectra_content']['#markup'] = 'Spectra Content Homepage.';
        return $form;
    }

}
