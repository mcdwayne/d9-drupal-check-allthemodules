<?php

namespace Drupal\entity_counter_webform\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_counter\Plugin\EntityCounterSourceWithEntityConditionsInterface;

/**
 * Defines the interface for webform entity counter sources.
 *
 * @see \Drupal\entity_counter\Annotation\EntityCounterSource
 * @see \Drupal\entity_counter_webform\Plugin\WebformEntityCounterSourceBase
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceManagerInterface
 * @see plugin_api
 */
interface WebformEntityCounterSourceBaseInterface extends EntityCounterSourceWithEntityConditionsInterface {

  /**
   * Cancels a transaction associated an entity.
   *
   * If you want to cancel a specific transaction use this other method:
   * \Drupal\entity_counter\Entity\CounterTransactionInterface::cancel().
   *
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The entity type that produces the transaction.
   * @param string|null $log_message
   *   The transaction log message.
   *
   * @return \Drupal\entity_counter\Entity\CounterTransactionInterface
   *   The created transaction.
   */
  public function cancelTransaction(EntityInterface $source_entity, string $log_message = NULL);

}
