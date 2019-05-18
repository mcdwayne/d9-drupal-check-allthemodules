<?php

namespace Drupal\field_encrypt\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\field_encrypt\FieldEncryptProcessEntitiesInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base functionality for the EncryptedFieldUpdate Queue Workers.
 */
abstract class EncryptedFieldUpdateBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The service the process entities.
   *
   * @var \Drupal\field_encrypt\FieldEncryptProcessEntitiesInterface.
   */
  protected $processEntitiesService;

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Creates a new EncryptedFieldUpdate object.
   *
   * @param \Drupal\field_encrypt\FieldEncryptProcessEntitiesInterface $process_entities_service
   *   The service the process entities.
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(FieldEncryptProcessEntitiesInterface $process_entities_service, ConfigFactoryInterface $config_factory) {
    $this->processEntitiesService = $process_entities_service;
    $this->config = $config_factory->get('field_encrypt.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('field_encrypt.process_entities'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // @TODO: remove config, if batch_size setting is irrelevant.
    $this->processEntitiesService->updateStoredField(
      $data['field_name'],
      $data['entity_type'],
      $data['original_config'],
      $data['entity_id']
    );
  }

}
