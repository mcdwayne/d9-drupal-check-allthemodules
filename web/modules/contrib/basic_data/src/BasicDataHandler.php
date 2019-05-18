<?php

namespace Drupal\basic_data;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\basic_data\Entity\BasicDataInterface;
use Drupal\basic_data\Entity\BasicData;

/**
 * Class BasicDataHandler.
 */
class BasicDataHandler implements BasicDataHandlerInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * BasicDataManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * @inheritdoc
   */
  public function createBasicData($type, $body, $values = null) {
    // Process values from the '$body' (an expected json dump).
    if ($values === null) {
      $values = json_decode($body);
    }

    // Load the basic_data storage.
    if ($storage = $this->getStorage()) {

      // Look for a provided ID in the values and load existing entities.
      if (isset($values->id)) {
        $existing_data = $storage->loadByProperties(['id' => $values->id]);
        // Multiple entries exist, use the first found (edge case).
        if (is_array($existing_data) && !empty($existing_data)) {
          $existing_data = reset($existing_data);
        }

        // Must be of the expected type to be processed.
        if ($existing_data instanceof BasicDataInterface) {
          // Use the existing data loaded via ID value.
          $data = $existing_data;
          // Update the existing data.
          $data->setData($body);
        }
      }

      // When no existing data is found, create a new basic_data entity.
      if (!isset($existing_data) || empty($existing_data)) {
        $data = BasicData::create(['type' => $type])->setData($body);
      }

      // Save basic_data entity or log an error.
      if (isset($data)) {
        return $this->saveData($data);
      }
    }
    return FALSE;
  }

  /**
   * @inheritdoc
   */
  public function saveData(BasicDataInterface $data) {
    try {
      $data->save();
    } catch (EntityStorageException $e) {
      $this->logError($e->getMessage());
    }
    return $data;
  }

  /**
   * @inheritdoc
   */
  public function logError($message) {
    $this->loggerFactory->get('basic_data')->error($message);
  }

  /**
   * @inheritdoc
   */
  public function getStorage() {
    try {
      return $this->entityTypeManager->getStorage('basic_data');
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->logError($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->logError($e->getMessage());
    }
    return FALSE;
  }

}
