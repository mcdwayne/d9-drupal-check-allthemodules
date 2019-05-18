<?php

namespace Drupal\commerce_advancedqueue\Plugin\views\argument_validator;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;

/**
 * Defines an argument validator plugin for queue backends.
 *
 * @ViewsArgumentValidator(
 *   id = "advancedqueue_commerce_order_backend",
 *   title = @Translation("Commerce Order Queue backend"),
 * )
 */
class CommerceOrderQueueBackend extends ArgumentValidatorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function validateArgument($arg) {
    $queue = Queue::load($arg);
    return $queue && $queue->getBackendId() === 'database_commerce_order_job';
  }

}
