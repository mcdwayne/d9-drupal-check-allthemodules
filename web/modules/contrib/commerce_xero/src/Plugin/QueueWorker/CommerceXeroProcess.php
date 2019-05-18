<?php

namespace Drupal\commerce_xero\Plugin\QueueWorker;

use Drupal\commerce_xero\CommerceXeroDataInterface;
use Drupal\commerce_xero\CommerceXeroProcessorManager;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\TypedData\ComplexDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Commerce Xero Process Queue Worker.
 *
 * @QueueWorker(
 *   id = "commerce_xero_process",
 *   title = @Translation("Commerce Xero Process"),
 *   cron = {"time" = 60}
 * )
 */
class CommerceXeroProcess extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Commerce xero processor manager.
   *
   * @var \Drupal\commerce_xero\CommerceXeroProcessorManager
   */
  protected $processor;

  /**
   * The commerce_xero queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The logger interface for error handling.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition from discovery.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\commerce_xero\CommerceXeroProcessorManager $processor
   *   The commerce_xero_processor.manager service.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger.factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, CommerceXeroProcessorManager $processor, QueueFactory $queueFactory, Connection $connection, LoggerChannelFactoryInterface $loggerFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
    $this->processor = $processor;
    $this->queue = $queueFactory->get('commerce_xero_process');
    $this->connection = $connection;
    $this->logger = $loggerFactory->get('commerce_xero');
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data instanceof CommerceXeroDataInterface) {
      $state = $data->getExecutionState();

      try {
        /** @var \Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface $strategy */
        $strategy = $this->entityTypeManager
          ->getStorage('commerce_xero_strategy')
          ->load($data->getStrategyEntityId());
        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = $this->entityTypeManager
          ->getStorage('commerce_payment')
          ->load($data->getPaymentEntityId());
        $value = $data->getData();

        $success = $this->processor->process($strategy, $payment, $value, $state);

        if ($state === 'process' && ($value !== NULL || !$success)) {
          // Adds back to the queue after changing execution state to "send".
          $data->setData($value);
          $data->setExecutionState('send');

          $this->queue->createItem($data);
        }
        elseif ($state === 'send' && $success) {
          $this->logger->info(
            'Successfully posted @type to Xero for payment @id using strategy @strategy',
            [
              '@type' => $data->getData()->getDataDefinition()->getDataType(),
              '@id' => $data->getPaymentEntityId(),
              '@strategy' => $data->getStrategyEntityId(),
            ]
          );
        }
        elseif ($state === 'send' && !$success) {
          $this->logger->error(
            'Error posting @type to Xero for payment @id using strategy @strategy',
            [
              '@type' => $data->getData()->getDataDefinition()->getDataType(),
              '@id' => $data->getPaymentEntityId(),
              '@strategy' => $data->getStrategyEntityId(),
            ]
          );
        }
      }
      catch (\Exception $e) {
        $value = isset($value) ? $value : NULL;
        $this->addToQueue($data, $value, $state);
        $this->logger->error(
          'Execution %state Strategy %strategy Payment %payment Data Type %type: %message',
          [
            '%state' => $state,
            '%strategy' => $data->getStrategyEntityId(),
            '%payment' => $data->getPaymentEntityId(),
            '%type' => $data->getData()->getDataDefinition()->getDataType(),
            '%message' => $e->getMessage(),
          ]
        );
      }
    }
  }

  /**
   * Adds the item back to the queue in the given execution state.
   *
   * @param \Drupal\commerce_xero\CommerceXeroDataInterface $data
   *   The queue data.
   * @param \Drupal\Core\TypedData\ComplexDataInterface $value
   *   The typed data to set on the queue data.
   * @param string $state
   *   The execption state to change to.
   */
  protected function addToQueue(CommerceXeroDataInterface $data, ComplexDataInterface $value = NULL, $state = 'send') {
    if ($value !== NULL) {
      $data->setData($value);
    }

    $data->setExecutionState($state);

    if ($data->exceededPoisonThreshhold() || $state === 'send') {
      // Creates a new item and immediately claims it for an indefinite time
      // i.e. 50 years from the current time/date. Hopefully you won't be using
      // this for that long without manually processing or removing from the
      // queue.
      $this->logger->error(
        'Exceeded queue failure threshhold for strategy %strategy payment %payment. This item has been added to the poison queue for debugging.',
        [
          '%strategy' => $data->getStrategyEntityId(),
          '%payment' => $data->getPaymentEntityId(),
        ]
      );
      $data->setExecutionState('poison');
      $id = $this->queue->createItem($data);
      $this->connection->update('queue')
        ->fields(['expire' => time() + 1577880000])
        ->condition('item_id', $id)
        ->execute();
    }
    else {
      $data->incrementCount();
      $this->queue->createItem($data);
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('commerce_xero_processor.manager'),
      $container->get('queue'),
      $container->get('database'),
      $container->get('logger.factory')
    );
  }

}
