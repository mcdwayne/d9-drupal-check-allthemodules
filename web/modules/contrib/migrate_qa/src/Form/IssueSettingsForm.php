<?php

namespace Drupal\migrate_qa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IssueSettingsForm
 *
 * @ingroup migrate_qa
 */
class IssueSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_qa_issue_settings';
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
    $form['migrate_qa_issue_settings']['#markup'] = 'Settings for Migrate QA Issue.';
    return $form;
  }

}
