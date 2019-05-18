<?php

namespace Drupal\config_actions\Plugin\ConfigActionsSource;

use Drupal\config_actions\ConfigActionsSourceBase;
use Drupal\config_actions\ConfigActionsServiceInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Plugin for config id from the active store.
 *
 * @ConfigActionsSource(
 *   id = "id",
 *   description = @Translation("Use a configuration id."),
 * )
 */
class ConfigActionsId extends ConfigActionsSourceBase {

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The cached configuration item for the source.
   * @var \Drupal\Core\Config\Config
   */
  protected $configItem;

  /**
   * Constructs a new ConfigActionsSource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param ConfigActionsServiceInterface $config_action_service
   *   The ConfigActionsService from the container.
   * @param ConfigFactory $config_factory
   *   The ConfigFactory from the container.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The configuration storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              ConfigActionsServiceInterface $config_action_service,
                              ConfigFactory $config_factory,
                              StorageInterface $config_storage,
                              ConfigManagerInterface $config_manager,
                              MessengerInterface $messenger) {
    $this->configFactory = $config_factory;
    $this->configStorage = $config_storage;
    $this->configManager = $config_manager;
    $this->messenger = $messenger;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_action_service);
  }

  /**
   * Create a plugin instance from the container
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var ConfigActionsServiceInterface $config_action_service */
    $config_action_service = $container->get('config_actions');
    /** @var ConfigFactory $config_factory */
    $config_factory = $container->get('config.factory');
    /** @var StorageInterface $config_storage */
    $config_storage = $container->get('config.storage');
    /** @var ConfigManagerInterface $config_manager */
    $config_manager = $container->get('config.manager');
    /** @var MessengerInterface $messenger */
    $messenger = $container->get('messenger');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $config_action_service,
      $config_factory,
      $config_storage,
      $config_manager,
      $messenger
    );
  }

  /**
   * {@inheritdoc}
   */
  public function detect($source) {
    // Check for a valid configuration id.
    return (is_string($source) && (strpos($source, '.') > 0));
  }

  /**
   * Fetch the configuration item
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\Config
   */
  protected function getConfigItem() {
    if (!isset($this->configItem)) {
      $this->configItem = $this->configFactory->getEditable($this->sourceId);
    }
    return $this->configItem;
  }

  /**
   * {@inheritdoc}
   */
  public function init($source, $base = '') {
    // If we are just starting, or loading a new source, clear the item cache.
    if ($source != $this->sourceId) {
      unset($this->configItem);
    }
    parent::init($source, $base);
  }

  /**
   * {@inheritdoc}
   */
  public function doLoad() {
    $this->setMerge(FALSE);
    $config_item = $this->getConfigItem();
    $data = !empty($config_item) ? $config_item->get() : [];
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function doSave($data) {
    $config_item = $this->getConfigItem();
    if (empty($config_item)) {
      return FALSE;
    }
    else if (empty($data)) {
      $this->messenger->addMessage($this->t('Deleted %name', array('%name' => $this->sourceId)));
      $config_item->delete();
    }
    else {
      $config_item->setData($data);

      // Save any related entity for this config.
      // Taken from ConfigInstaller::createConfiguration()
      if ($entity_type = $this->configManager->getEntityTypeIdByName($this->sourceId)) {
        /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $entity_storage */
        $entity_storage = $this->configManager
          ->getEntityManager()
          ->getStorage($entity_type);
        // It is possible that secondary writes can occur during configuration
        // creation. Updates of such configuration are allowed.
        if ($this->configStorage->exists($this->sourceId)) {
          $id = $entity_storage->getIDFromConfigName($this->sourceId, $entity_storage->getEntityType()
            ->getConfigPrefix());
          $entity = $entity_storage->load($id);
          $this->messenger->addMessage($this->t('Updated %name', array('%name' => $this->sourceId)));
          /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
          $entity = $entity_storage->updateFromStorageRecord($entity, $config_item->get());
        }
        else {
          $this->messenger->addMessage($this->t('Created %name', array('%name' => $this->sourceId)));
          $entity = $entity_storage->createFromStorageRecord($config_item->get());
        }
        if ($entity->isInstallable()) {
          $entity->trustData()->save();
        }
      }
      else {
        $this->messenger->addMessage($this->t('Updated %name', array('%name' => $this->sourceId)));
        $config_item->save();
      }
    }
    return TRUE;
  }

}
