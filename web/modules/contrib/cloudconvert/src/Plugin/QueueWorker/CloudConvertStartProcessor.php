<?php

namespace Drupal\cloudconvert\Plugin\QueueWorker;

use Drupal\cloudconvert\CloudConvertProcessorInterface;
use Drupal\cloudconvert\Event\CloudConvertStartEvent;
use Drupal\cloudconvert\Parameters;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Cloud Convert Start Processor.
 *
 * @QueueWorker(
 *   id = "cloudconvert_start_processor",
 *   title = @Translation("CloudConvert Start Processor"),
 *   cron = {"time" = 60}
 * )
 */
class CloudConvertStartProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * CloudConvert Processor.
   *
   * @var \Drupal\cloudconvert\CloudConvertProcessorInterface
   */
  protected $cloudConvertProcessor;

  /**
   * Event Dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, CloudConvertProcessorInterface $cloudConvertProcessor, EventDispatcherInterface $eventDispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->cloudConvertProcessor = $cloudConvertProcessor;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('cloudconvert.processor'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function processItem($data) {
    if (!isset($data->cloudconvert_task_id)) {
      return;
    }

    $cloudConvertTaskId = $data->cloudconvert_task_id;
    $cloudConvertTaskStorage = $this->entityTypeManager->getStorage('cloudconvert_task');

    /** @var \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudConvertTask */
    $cloudConvertTask = $cloudConvertTaskStorage->load($cloudConvertTaskId);

    if (!$cloudConvertTask) {
      return;
    }

    $parameters = new Parameters($data->parameters);
    $this->cloudConvertProcessor->createProcess($cloudConvertTask, $parameters);

    $uploadParameters = new Parameters([
      'input' => 'upload',
      'callback' => $this->cloudConvertProcessor->getCallbackUrl($cloudConvertTask)
        ->toString(),
    ]);

    $cloudConvertTask->updateProcessParameters($uploadParameters->getParameters());
    $process = $this->cloudConvertProcessor->startProcess($cloudConvertTask);
    $cloudConvertTask->save();

    $event = new CloudConvertStartEvent($cloudConvertTask, $process->getData());
    $this->eventDispatcher->dispatch($event::START, $event);
  }

}
