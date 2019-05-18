<?php

namespace Drupal\migrate_qa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FlagSettingsForm
 *
 * @ingroup migrate_qa
 */
class FlagSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_qa_flag_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of function from an abstract class.
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['migrate_qa_flag_settings']['#markup'] = 'Settings for Migrate QA Flag.';
    return $form;
  }

}
