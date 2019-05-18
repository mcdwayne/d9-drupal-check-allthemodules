<?php

namespace Drupal\mass_contact\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\mass_contact\MassContactInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sends a mass contact message to a given user.
 *
 * @see \Drupal\mass_contact\Plugin\QueueWorker\QueueMessages
 *
 * @QueueWorker(
 *   id = "mass_contact_queue_messages",
 *   title = @Translation("Queues a mass contact message for individual processing")
 * )
 */
class QueueMessages extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The mass contact helper service.
   *
   * @var \Drupal\mass_contact\MassContactInterface
   */
  protected $massContact;

  /**
   * Queue worker constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\mass_contact\MassContactInterface $mass_contact
   *   The mass contact helper service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MassContactInterface $mass_contact) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->massContact = $mass_contact;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('mass_contact')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->massContact->queueRecipients($data['message'], $data['configuration']);
  }

}
