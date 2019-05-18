<?php

/**
 * @file
 * Contains \Drupal\schema\Form\SchemaSettingsForm.
 */

namespace Drupal\schema\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class SchemaSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schema_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('schema.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('schema.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }


  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $connection_options = schema_get_connection_options();
    $form['schema_database_connection'] = array(
      '#type' => 'select',
      '#title' => t('Database connection to use'),
      '#default_value' => \Drupal::config('schema.settings')->get('schema_database_connection'),
      '#options' => $connection_options,
      '#description' => t('If you use a secondary database other than the default Drupal database you can select it here and use schema\'s "compare" and "inspect" functions on that other database.'),
      '#access' => count($connection_options) > 1,
    );
    $form['schema_status_report'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include schema comparison reports in site status report'),
      '#default_value' => \Drupal::config('schema.settings')->get('schema_status_report'),
      '#description' => t('When checked, schema comparison reports are run on the Administer page, and included in the site status report.'),
    );
    $form['schema_suppress_type_warnings'] = array(
      '#type' => 'checkbox',
      '#title' => t('Suppress schema warnings.'),
      '#default_value' => \Drupal::config('schema.settings')->get('schema_suppress_type_warnings'),
      '#description' => t('When checked, missing schema type warnings will be suppressed.'),
    );

    return parent::buildForm($form, $form_state);
  }
}
