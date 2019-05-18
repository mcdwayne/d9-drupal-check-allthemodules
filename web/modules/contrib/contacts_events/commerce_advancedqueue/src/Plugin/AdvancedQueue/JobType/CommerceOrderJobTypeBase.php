<?php

namespace Drupal\commerce_advancedqueue\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\commerce_advancedqueue\CommerceOrderJob;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for handling jobs for orders.
 */
abstract class CommerceOrderJobTypeBase extends JobTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The order entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $order_storage
   *   The order entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $order_storage, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->orderStorage = $order_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('commerce_order'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job) {
    // Ensure we have the right job type.
    if (!($job instanceof CommerceOrderJob)) {
      return JobResult::failure('Invalid job', 0);
    }

    /* @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->orderStorage->load($job->getOrderId());
    if (!$order) {
      return JobResult::failure('Order cannot be loaded', 0);
    }

    $transaction = $this->connection->startTransaction();
    try {
      $result = $this->doProcess($order, $job);

      // If a job failed, undo anything that happened.
      if ($result->getState() == Job::STATE_FAILURE) {
        $transaction->rollBack();
        $job->setOrderNeedsSave(FALSE);
        return $result;
      }

      // If we are not deferring order save, process it now.
      if ($job->orderNeedsSave() && !$job->deferOrderSave()) {
        $order->save();
        $job->setOrderNeedsSave(FALSE);
      }

      return $result;
    }
    // On an entity storage exception, allow the item to be retried with a short
    // delay.
    catch (EntityStorageException $exception) {
      $transaction->rollBack();
      $job->setOrderNeedsSave(FALSE);
      return JobResult::failure($exception->getMessage(), 5, 60);
    }
    // On all other exceptions, leave retries down the the job type.
    catch (\Exception $exception) {
      $transaction->rollBack();
      $job->setOrderNeedsSave(FALSE);
      return JobResult::failure($exception->getMessage());
    }
  }

  /**
   * Handle the actual processing of the job.
   *
   * Orders should generally not be saved here, but rather
   * $job->setOrderNeedsSave() should be called if an order needs saving. If an
   * immediate save is required, $job->setDeferOrderSave(FALSE) should be set.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order being processed.
   * @param \Drupal\commerce_advancedqueue\CommerceOrderJob $job
   *   The job being processed.
   *
   * @return \Drupal\advancedqueue\JobResult
   *   The result of the job. On a failure, the transaction will be rolled back.
   */
  abstract protected function doProcess(OrderInterface $order, CommerceOrderJob $job);

}
