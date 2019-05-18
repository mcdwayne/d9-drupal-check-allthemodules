<?php

namespace Drupal\client_connection\Entity\Storage;

use Drupal\client_connection\ClientConnectionManager;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a storage handler class for client connection config.
 */
class ClientConnectionConfigStorage extends ConfigEntityStorage implements ClientConnectionConfigStorageInterface {

  /**
   * The client connection manager.
   *
   * @var \Drupal\client_connection\ClientConnectionManager
   */
  protected $clientManager;

  /**
   * Constructs a ClientConnectionConfigStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\client_connection\ClientConnectionManager $client_manager
   *   The client connection manager.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, ClientConnectionManager $client_manager) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);

    $this->clientManager = $client_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('plugin.manager.client_connection')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function findId($plugin_id, $instance_id = 'default', $channel_id = 'site') {
    $query = $this->getQuery();

    // Support for multiple channel options being passed-in.
    $or_group = $query->orConditionGroup();
    $channels = (is_array($channel_id)) ? $channel_id : [$channel_id];
    foreach ($channels as $channel) {
      $or_group->condition('channels.*', $channel);
    }

    $results = $query
      ->condition('pluginId', $plugin_id)
      ->condition('instanceId', $instance_id)
      ->condition($or_group)
      ->execute();

    return !empty($results) ? current($results) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPreSave(EntityInterface $entity) {
    /** @var \Drupal\client_connection\Entity\ClientConnectionConfigInterface $entity */
    $id = parent::doPreSave($entity);

    // Make sure the plugin is set and exists.
    if (is_null($entity->getPluginId()) || !$this->clientManager->hasDefinition($entity->getPluginId())) {
      throw new ConfigValueException("Attempt to create a Client Connection Configuration without a proper plugin ID.");
    }

    // Make sure the plugin instance-ID is set and exists.
    if (is_null($entity->getInstanceId())) {
      throw new ConfigValueException("Attempt to create a Client Connection Configuration without a proper plugin instance-ID.");
    }

    // Make sure this plugin id isn't already paired with this instance-ID.
    $existing_id = $this->findId($entity->getPluginId(), $entity->getInstanceId(), $entity->getChannels());
    if (!is_null($existing_id) && $id !== $existing_id) {
      throw new ConfigValueException("Attempt to create a Client Connection Configuration with a plugin ID - instance-ID pair that already exists.");
    }

    return $id;
  }

}
