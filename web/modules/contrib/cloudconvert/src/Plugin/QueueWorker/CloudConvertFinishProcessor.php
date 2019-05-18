<?php

namespace Drupal\cloudconvert\Plugin\QueueWorker;

use Drupal\cloudconvert\CloudConvertProcessorInterface;
use Drupal\cloudconvert\Event\CloudConvertFinishEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Cloud Convert Finish PRocessor.
 *
 * @QueueWorker(
 *   id = "cloudconvert_finish_processor",
 *   title = @Translation("CloudConvert Finish Processor"),
 *   cron = {"time" = 60}
 * )
 */
class CloudConvertFinishProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function processItem($data) {
    $cloudConvertTaskStorage = $this->entityTypeManager->getStorage('cloudconvert_task');
    $cloudConvertTaskTypeStorage = $this->entityTypeManager->getStorage('cloudconvert_task_type');

    if (!isset($data->cloudconvert_task_id)) {
      return;
    }

    $cloudConvertTaskId = $data->cloudconvert_task_id;
    /** @var \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudConvertTask */
    $cloudConvertTask = $cloudConvertTaskStorage->load($cloudConvertTaskId);

    if (!$cloudConvertTask) {
      return;
    }

    $cloudConvertTaskTypeId = $cloudConvertTask->bundle();
    /** @var \Drupal\cloudconvert\Entity\CloudConvertTaskTypeInterface $cloudConvertTaskType */
    $cloudConvertTaskType = $cloudConvertTaskTypeStorage->load($cloudConvertTaskTypeId);

    $procesMethod = $cloudConvertTaskType->getFinishMethod();
    $process = $this->cloudConvertProcessor->getProcess($cloudConvertTask);

    $result = NULL;
    if ($procesMethod === 'info') {
      $result = $this->cloudConvertProcessor->gatherInfo($process);
    }
    elseif ($procesMethod === 'download') {
      $result = $this->cloudConvertProcessor->downloadFile($process);
    }

    $event = new CloudConvertFinishEvent($cloudConvertTask, $result);
    $this->eventDispatcher->dispatch($event::FINISH, $event);
    $this->cloudConvertProcessor->finishProcess($cloudConvertTask);
  }

}
