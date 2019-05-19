<?php

namespace Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface;

/**
 * A base class for task plugins.
 */
abstract class TaskPluginBase extends PluginBase implements TaskPluginInterface {

  /**
   * The scheduled task this task is attached to.
   *
   * @var \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface
   */
  protected $scheduledTask;

  /**
   * TaskPluginBase constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function onSuccess() {
  }

  /**
   * {@inheritdoc}
   */
  public function onFailure() {
  }

  /**
   * {@inheritdoc}
   */
  public function setScheduledTask(WebformScheduledTaskInterface $scheduledTask) {
    $this->scheduledTask = $scheduledTask;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getScheduledTask() {
    return $this->scheduledTask;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
