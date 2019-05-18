<?php

namespace Drupal\config_pages_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ConfigPagesImportForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'config_pages_imports_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $entities = [];

    foreach (\Drupal::service('config.typed')->getDefinitions() as $key => $definition) {
      if ($definition['type'] == 'mapping') {
        $entities[] = $key;
      }
    }

    $form['config_entity'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose config for import:'),
      '#options' => array_combine($entities, $entities),
      '#default_value' => 'config_pages_import_test',
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Import',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    try {
      $import = \Drupal::service('config_pages_import');
      $import->import($form_state->getValue('config_entity'));
    } catch (\Exception $e) {
      \Drupal::messenger()->addMessage('Config import was failed', 'error');
    }

  }
}