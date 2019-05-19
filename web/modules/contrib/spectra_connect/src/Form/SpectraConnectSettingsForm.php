<?php

namespace Drupal\spectra_connect\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SpectraConnectSettingsForm.
 *
 * @package Drupal\spectra_connect\Form
 *
 * @ingroup spectra_connect
 */
class SpectraConnectSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'spectra_connect_settings';
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
    $form['spectra_connect_settings']['#markup'] = $this->t('Settings form for Spectra Spectra Connect Entity Settings. Manage field settings here.');
    return $form;
  }

}
