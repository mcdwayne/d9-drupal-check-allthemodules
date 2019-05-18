<?php

namespace Drupal\config2php\Form;

use Drupal\config\Form\ConfigSingleExportForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for exporting a single configuration file.
 */
class Config2PhpForm extends ConfigSingleExportForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config2php_form';
  }

  /**
   * {@inheritdoc}
   */
  public function updateExport($form, FormStateInterface $form_state) {
    $name = $form_state->getValue('config_name');

    if ($form_state->getValue('config_type') !== 'system.simple') {
      $type = $form_state->getValue('config_type');
      $definition = $this->entityManager->getDefinition($type);
      $name = $definition->getConfigPrefix() . '.' . $name;
    }

    $value = var_export($this->configStorage->read($name), TRUE);

    $replacement = "(\s+\'[^\']+\'\s\=\>\s)";

    $replacements = [
      '^array \(' => '[',
      '\)$' => ']',
      $replacement . "\n\s+array\s\(" => '$1[',
      "\[\n\s+\)" => '[]',
      '\d+\s\=\>\s' => '',
      $replacement . 'false\,' => '$1FALSE,',
      $replacement . 'true\,' => '$1TRUE,',
    ];

    foreach ($replacements as $pattern => $replacement) {
      $value = preg_replace('/' . $pattern . '/', $replacement, $value);
    }

    $count = 0;

    do {
      $value = preg_replace("/(,\n\s+)\),/", '$1],', $value, -1, $count);
    } while ($count);

    $form['export']['#value'] = $value;

    $form['export']['#description'] = $this->t('Configuration name: %name', [
      '%name' => $name,
    ]);

    return $form['export'];
  }

}
