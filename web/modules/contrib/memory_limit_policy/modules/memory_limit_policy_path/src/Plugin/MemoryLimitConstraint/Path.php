<?php

namespace Drupal\memory_limit_policy_path\Plugin\MemoryLimitConstraint;

use Drupal\Core\Form\FormStateInterface;
use Drupal\memory_limit_policy\MemoryLimitConstraintBase;

/**
 * Configure the memory limit based on path.
 *
 * @MemoryLimitConstraint(
 *   id = "path",
 *   title = @Translation("Path"),
 *   description = @Translation("Provide a list of path where the memory limit must be overridden.")
 * )
 */
class Path extends MemoryLimitConstraintBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths'),
      '#description' => $this->t('Enter one path per line.'),
      '#default_value' => $this->getConfiguration()['paths'] ?? '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['paths'] = $form_state->getValue('paths');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $paths = explode(PHP_EOL, $this->configuration['paths']);
    array_walk($paths, function (&$path) {
      $path = rtrim(trim(trim($path, "\r"), '/'), '/');
    });
    $paths = implode(', ', $paths);

    return $this->t('Paths: @paths', ['@paths' => $paths]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $current_path = rtrim(trim(\Drupal::service('path.current')->getPath(), '/'), '/');

    foreach (explode(PHP_EOL, $this->configuration['paths']) as $path) {
      // Remove \r as well if string contains \r\n.
      $path = rtrim(trim(trim($path, "\r"), '/'), '/');

      if (fnmatch($path, $current_path)) {
        return TRUE;
      }
    }

    return parent::evaluate();
  }

}
