<?php

namespace Drupal\client_connection\Resolver;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Returns the default configuration for a particular plugin, if known.
 */
class DefaultConnectionResolver implements ConnectionResolverInterface {

  /**
   * The client connection configuration storage.
   *
   * @var \Drupal\client_connection\Entity\Storage\ClientConnectionConfigStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new DefaultConnectionResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('client_connection_config');
  }

  /**
   * {@inheritdoc}
   */
  public function applies($plugin_id, array $contexts, $channel_id = 'site') {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve($plugin_id, array $contexts, $channel_id = 'site') {
    return $this->storage->findId($plugin_id, 'default', $channel_id);
  }

}
