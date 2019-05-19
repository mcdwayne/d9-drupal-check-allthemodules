<?php

namespace Drupal\update_runner\Plugin\UpdateRunnerProcessorPlugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\update_runner\Event\UpdateRunnerEvent;
use Drupal\update_runner\Plugin\UpdateRunnerProcessorPluginManager;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Base class for update runner processor plugins.
 *
 * @property \GuzzleHttp\Client httpClient
 */
class UpdateRunnerProcessorPlugin extends PluginBase implements ContainerFactoryPluginInterface, PluginInspectionInterface {

  protected $defaultValues;
  protected $httpClient;

  /**
   * Constructs a Automatic object.
   *
   * @param array $configuration
   *   Configuration array.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \GuzzleHttp\Client $http_client
   *   HttpClient.
   * @param \Drupal\update_runner\Plugin\UpdateRunnerProcessorPluginManager $pluginManager
   *   Plugin Manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $http_client, UpdateRunnerProcessorPluginManager $pluginManager, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->httpClient = $http_client;
    $this->configuration = $configuration;
    $this->pluginManager = $pluginManager;
    $this->entityTypeManager = $entity_type_manager;
    $this->event_dispatcher = $event_dispatcher;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('plugin.manager.update_runner_processor_plugin'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('logger.factory')->get('update')
    );
  }

  /**
   * Define form options for the processor.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   Processor being configured.
   *
   * @return array
   *   Form array being returned.
   */
  public function formOptions(EntityInterface $entity = NULL) {

    $formOptions = [];
    $defaultValues = [];

    if (!empty($entity) && !empty($entity->get('data'))) {
      $this->defaultValues = unserialize($entity->get('data'));
    }

    $formOptions['processor_config'] = [
      '#type' => 'fieldset',
      '#title' => t('Processor configuration'),
    ];

    $formOptions['processor_config']['update_type'] = [
      '#type' => 'radios',
      '#title' => t('Update types'),
      '#required' => TRUE,
      '#description' => t('Types of updates to respond to'),
      '#default_value' => !empty($this->defaultValues['update_type']) ? $this->defaultValues['update_type'] : 'security',
      '#options' => [
        'all' => t('All newer versions'),
        'security' => t('Only security updates'),
      ],
    ];

    $formOptions['processor_config']['notify_on_create'] = [
      '#type' => 'checkbox',
      '#title' => t('Notify on job created'),
      '#description' => t('Notify site admin by email when job is created'),
      '#default_value' => !empty($this->defaultValues['notify_on_create']) ? $this->defaultValues['notify_on_create'] : '',
    ];

    $formOptions['processor_config']['notify_on_complete'] = [
      '#type' => 'checkbox',
      '#title' => t('Notify on job completed'),
      '#description' => t('Notify site admin by email when job is completed'),
      '#default_value' => !empty($this->defaultValues['notify_on_complete']) ? $this->defaultValues['notify_on_complete'] : '',
    ];

    return $formOptions;

  }

  /**
   * Define keys used in the configuration.
   */
  public function optionsKeys() {
    return ['update_type', 'notify_on_create', 'notify_on_complete'];
  }

  /**
   * Executed when job is created.
   */
  public function insert($values) {

    $entity = $this->entityTypeManager
      ->getStorage('update_runner_job')
      ->create($values);
    $entity->save();

    // Emits event.
    $updateRunnerJobEvent = new UpdateRunnerEvent($entity);
    $this->event_dispatcher->dispatch(UpdateRunnerEvent::UPDATE_RUNNER_JOB_CREATED, $updateRunnerJobEvent);

  }

  /**
   * Execute job.
   */
  public function run($job) {
    // Emits event.
    $updateRunnerJobEvent = new UpdateRunnerEvent($job);
    $this->event_dispatcher->dispatch(UpdateRunnerEvent::UPDATE_RUNNER_JOB_COMPLETED, $updateRunnerJobEvent);

    return UPDATE_RUNNER_JOB_PROCESSED;
  }

}
