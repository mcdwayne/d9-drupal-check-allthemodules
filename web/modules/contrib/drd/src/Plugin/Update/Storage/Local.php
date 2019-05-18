<?php

namespace Drupal\drd\Plugin\Update\Storage;

use Drupal\Core\Form\FormStateInterface;
use Drupal\drd\Update\PluginStorageInterface;

/**
 * Provides a local storage update plugin.
 *
 * @Update(
 *  id = "local",
 *  admin_label = @Translation("Local"),
 * )
 */
class Local extends Base {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'workingdir' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $element = parent::buildConfigurationForm($form, $form_state);

    $element['workingdir'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Local working directory'),
      '#default_value' => $this->configuration['workingdir'],
      '#states' => [
        'required' => $this->condition,
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $value = $this->getFormValue($form_state, 'workingdir');
    if (!$this->fs->exists($value)) {
      $form_state->setError($form[$this->configFormParent][$this->getType()][$this->pluginId]['workingdir'], $this->t('Directory not found.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['workingdir'] = $this->getFormValue($form_state, 'workingdir');
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkingDirectory() {
    $this->workingDirectory = $this->configuration['workingdir'];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareWorkingDirectory() {
    // Nothing to do as we have a locally available working directory.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function saveWorkingDirectory() {
    // Nothing to do as we have a locally available working directory.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(PluginStorageInterface $storage) {
    // Nothing to cleanup as we want to keep the local working directory as is.
    return $this;
  }

}
