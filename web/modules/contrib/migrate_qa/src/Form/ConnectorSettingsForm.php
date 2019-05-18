<?php

namespace Drupal\migrate_qa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConnectorSettingsForm
 *
 * @ingroup migrate_qa
 */
class ConnectorSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_qa_connector_settings';
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
    $form['migrate_qa_connector_settings']['#markup'] = 'Settings for Migrate QA Connector.';
    return $form;
  }

}
