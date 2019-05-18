<?php

namespace Drupal\entity_log;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class EntityLogService.
 *
 * @package Drupal\entity_log
 */
class EntityLogService implements EntityLogServiceInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Config\ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   *   Logger channel.
   */
  protected $logger;

  /**
   * EntityLogService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EntityTypeManager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   ConfigFactory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   Logger channel factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $logger_channel_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $configFactory;
    $this->logger = $logger_channel_factory->get('entity_log');
  }

  /**
   * Returns false or array of fields for logging.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return array|bool
   *   Entity set for logging.
   */
  public function entitySetForLogging(EntityInterface $entity) {
    if ($this->configFactory->get('entity_log.configuration')->get('log_in_logger') ||
      $this->configFactory->get('entity_log.configuration')->get('log_in_entity')) {
      $config = $this->configFactory->get('entity_log.configuration')->get($entity->getEntityTypeId());
      return isset($config[$entity->bundle()]['fields']) ? array_filter($config[$entity->bundle()]['fields']) : FALSE;
    }
    return FALSE;
  }

  /**
   * Log fields into Entity Log entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param array $fields
   *   Fields.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function logFields(EntityInterface $entity, array $fields) {
    foreach ($fields as $field) {
      if (method_exists($entity, 'get') && $entity->get($field) instanceof FieldItemList && isset($entity->original)) {
        $old_value = [];
        $new_value = [];
        /* @var \Drupal\options\Plugin\Field\FieldType\ListStringItem $item */
        foreach ($entity->get($field)->getIterator() as $item) {
          $new_value[] = $item->getString();
        }
        foreach ($entity->original->get($field)->getIterator() as $item) {
          $old_value[] = $item->getString();
        }
        if ($new_value != $old_value) {
          $old_value = implode(',', $old_value);
          $new_value = implode(',', $new_value);
          if ($this->configFactory->get('entity_log.configuration')->get('log_in_logger')) {
            $this->logger->info(
              t('Entity type: @type | Bundle: @bundle | Field: @field_name | Old: @old | New: @new', [
                '@type' => $entity->getEntityTypeId(),
                '@bundle' => $entity->bundle(),
                '@field_name' => $field,
                '@old' => !empty($old_value) ? $old_value : 'empty',
                '@new' => !empty($new_value) ? $new_value : 'empty',
              ]));
          }
          if ($this->configFactory->get('entity_log.configuration')->get('log_in_entity')) {
            $this->entityTypeManager->getStorage('entity_log')->create([
              "name" => $field,
              "label" => $entity->get($field)->getFieldDefinition()->getLabel(),
              "log_type" => $entity->getEntityTypeId(),
              'old_value' => !empty($old_value) ? $old_value : 'empty',
              'new_value' => !empty($new_value) ? $new_value : 'empty',
              "status" => 1,
              "entity_logged_id" => $entity,
            ])->save();
          }
        }
      }
    }
  }

}
