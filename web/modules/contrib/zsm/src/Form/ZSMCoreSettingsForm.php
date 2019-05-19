<?php
/**
 * @file
 * Contains \Drupal\zsm\Form\ZSMCoreSettingsForm.
 */

namespace Drupal\zsm\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\zsm\ZSMPluginManager;

/**
 * Class ContentEntityExampleSettingsForm.
 *
 * @package Drupal\zsm\Form
 *
 * @ingroup zsm
 */
class ZSMCoreSettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'zsm_core_settings';
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

    $form['zsm_core_settings']['#markup'] = 'Settings form for ZSM Core Settings. Manage field settings here.';
    return $form;
  }
}
