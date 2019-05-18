<?php

namespace Drupal\chatbot_api_entities\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a queue worker for pushing collections to remote chatbot services.
 *
 * @QueueWorker(
 *   id = "chatbot_api_entities_push",
 *   title = @Translation("Chatbot API entities push"),
 *   cron = {"time" = 60}
 * )
 */
class ChatbotEntityPushWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * State handler.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new ChatbotEntityPushWorker object.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   State storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->state = $state;
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
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $last = $this->state->get('chatbot_api_entities_last_' . $data['collection_id'], 0);
    if ($last > $data['created']) {
      // We've already done this since it was created, so no need to do it
      // again. This can occur if multiple entity updates result in the same
      // collection ID being queued more than once.
      return;
    }
    /** @var \Drupal\chatbot_api_entities\Entity\EntityCollectionInterface $collection */
    if ($collection = $this->entityTypeManager->getStorage('chatbot_api_entities_collection')->load($data['collection_id'])) {
      $collection->queryAndPush($this->entityTypeManager);
    }
    $this->state->set('chatbot_api_entities_last_' . $data['collection_id'], time());
  }

}
