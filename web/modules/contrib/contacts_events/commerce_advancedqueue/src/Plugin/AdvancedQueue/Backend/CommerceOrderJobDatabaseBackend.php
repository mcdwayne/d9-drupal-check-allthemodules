<?php

namespace Drupal\commerce_advancedqueue\Plugin\AdvancedQueue\Backend;

use Drupal\advancedqueue\Plugin\AdvancedQueue\Backend\Database;
use Drupal\commerce_advancedqueue\CommerceOrderJob;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides the database queue backend for Commerce Order Jobs.
 *
 * @AdvancedQueueBackend(
 *   id = "database_commerce_order_job",
 *   label = @Translation("Database: Commerce Order Job"),
 * )
 */
class CommerceOrderJobDatabaseBackend extends Database implements CommerceOrderJobBackendInterface {

  /**
   * {@inheritdoc}
   */
  const TABLE_NAME = 'advancedqueue_commerce_order';

  /**
   * {@inheritdoc}
   */
  const JOB_CLASS = CommerceOrderJob::class;

  /**
   * {@inheritdoc}
   */
  protected function getBaseQuery($order_id = NULL, ...$args) {
    $query = parent::getBaseQuery($order_id, ...$args);
    if ($order_id) {
      $query->condition('order_id', $order_id);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function schemaDefinition() {
    $schema = parent::schemaDefinition();
    $schema['fields']['order_id'] = [
      'type' => 'int',
      'not null' => TRUE,
      'description' => 'The order ID this job relates to.',
    ];
    $schema['indexes']['order_queue'] = $schema['indexes']['queue'];
    $schema['indexes']['order_queue'][] = 'order_id';
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function getViewsData() {
    $data = parent::getViewsData();

    $data[static::TABLE_NAME]['table']['base']['title'] = new TranslatableMarkup('Order Jobs');
    $data[static::TABLE_NAME]['table']['base']['help'] = new TranslatableMarkup('Contains a list of advanced queue jobs for Commerce Orders.');

    $data[static::TABLE_NAME]['order_id'] = [
      'title' => new TranslatableMarkup('Order ID'),
      'help' => new TranslatableMarkup('The ID of the order this job relates to.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'relationship' => [
        'base' => 'commerce_order',
        'base field' => 'order_id',
        'id' => 'standard',
        'label' => new TranslatableMarkup('Order for the Job'),
      ],
    ];

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function countJobsForOrder($order_id) {
    return $this->countJobs($order_id);
  }

  /**
   * {@inheritdoc}
   */
  public function claimJobForOrder($order_id) {
    return $this->claimJob($order_id);
  }

}
