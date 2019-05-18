<?php

namespace Drupal\config_delete\Form;

use Drupal\config\Form\ConfigSingleExportForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a single configuration file.
 *
 * @internal
 */
class ConfigDeleteForm extends ConfigSingleExportForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_type = $form_state->getValue('config_type');
    $config_name = $form_state->getValue('config_name');

    if ($form_state->getValue('config_type') !== 'system.simple') {
      $definition = $this->entityManager->getDefinition($config_type);
      $name = $definition->getConfigPrefix() . '.' . $config_name;
    }
    else {
      $name = $config_name;
    }

    $message = t('Configuration "@config_name" successfully deleted.', ['@config_name' => $name]);

    if ($form_state->getValue('delete_dependencies')) {
      $dependencies = \Drupal::configFactory()->get($name)->get('dependencies');
      if (isset($dependencies['config'])) {
        foreach ($dependencies['config'] as $key => $config_name) {
          $this->deleteConfig($config_name);
        }

        $message = t('Configuration "@config_name" and all its dependencies successfully deleted.', ['@config_name' => $name]);
      }
    }

    $this->deleteConfig($name);

    \Drupal::messenger()->addStatus($message);
  }

  /**
   * Deletes the configuration object.
   *
   * @param string $name
   *   The configuration name.
   */
  protected function deleteConfig($name) {
    \Drupal::configFactory()->getEditable($name)
      ->delete();
  }

}
