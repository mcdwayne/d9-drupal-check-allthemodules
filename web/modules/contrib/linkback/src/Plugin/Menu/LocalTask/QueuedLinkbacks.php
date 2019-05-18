<?php

namespace Drupal\linkback\Plugin\Menu\LocalTask;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a local task that shows the sent linkbacks.
 */
class QueuedLinkbacks extends LocalTaskDefault implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The linkback storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkbackStorage;

  /**
   * Construct the ReceivedLinkbacks object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $linkback_storage
   *   The linkback storage service.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      array $plugin_definition,
      EntityStorageInterface $linkback_storage
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $this->linkbackStorage = $linkback_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
      ContainerInterface $container,
      array $configuration,
      $plugin_id,
      $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('linkback')
    );
  }

}
