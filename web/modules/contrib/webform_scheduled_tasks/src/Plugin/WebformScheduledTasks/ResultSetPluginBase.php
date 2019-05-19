<?php

namespace Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for result set plugins.
 */
abstract class ResultSetPluginBase extends PluginBase implements ResultSetPluginInterface, ContainerFactoryPluginInterface {

  /**
   * A query object for submissions.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $submissionQuery;

  /**
   * The scheduled task this task is attached to.
   *
   * @var \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface
   */
  protected $scheduledTask;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('webform_submission')->getQuery()
    );
  }

  /**
   * TaskPluginBase constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryInterface $submissionQuery) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->submissionQuery = $submissionQuery;
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
    if ($summary = $this->getSummary()) {
      $form['summary'] = ['#markup' => $summary];
    }
    return $form;
  }

  /**
   * Get an optional summary for the plugin rendered in the settings form.
   *
   * @return string|null
   *   A summary.
   */
  protected function getSummary() {
    return NULL;
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
   * Initialize options likely to be common to most result sets.
   */
  protected function initializeQueryDefaults() {
    $this->submissionQuery->accessCheck(FALSE);
    $this->submissionQuery->condition('webform_id', $this->getScheduledTask()->getWebform()->id());
    $this->submissionQuery->condition('in_draft', FALSE);
    $this->submissionQuery->sort('sid');
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
