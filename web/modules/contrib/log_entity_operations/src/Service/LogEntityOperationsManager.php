<?php

namespace Drupal\log_entity_operations\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\log_entity_operations\ArrayDiff;
use Drupal\log_entity_operations\Form\LogEntityOperationsSettingsForm;

/**
 * Class LogEntityOperationsManager.
 *
 * @package Drupal\log_entity_operations\Service
 */
class LogEntityOperationsManager {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * LogEntityOperationsManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              ConfigFactoryInterface $config_factory,
                              LoggerChannelInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  /**
   * Log update.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity on which operations is performed.
   * @param string $operation
   *   Operation.
   */
  public function logUpdate(EntityInterface $entity, string $operation) {
    $type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    $allowed_for = $this->getConfig('enabled_for');

    // Confirm it is allowed for type and bundle.
    if (empty($allowed_for) || !in_array($type . '.' . $bundle, $allowed_for)) {
      $this->logger->error(print_r($allowed_for, TRUE));
      return;
    }

    $message = '@operation operation performed on entity of type: @type, bundle: @bundle, ID: @id for language @langcode.';
    $args = [
      '@operation' => $operation,
      '@type' => $type,
      '@bundle' => $bundle,
      '@id' => $entity->id(),
      '@langcode' => $entity->language()->getId(),
    ];

    // Get the diff if available and allowed.
    if ($operation = 'update' && isset($entity->originalArrayForDiff) && $this->getConfig('log_diff')) {
      $array1 = $this->filterRecursive($entity->originalArrayForDiff);
      $updated_entity = $this->entityTypeManager->getStorage($type)->load($entity->id());
      $array2 = $this->filterRecursive($updated_entity->toArray());
      $differ = new ArrayDiff();
      $args['@diff'] = json_encode($this->filterRecursive($differ->diff($array1, $array2)));
      $message .= ' Diff @diff';
    }

    $this->logger->info($message, $args);
  }

  /**
   * Get value from config.
   *
   * @param string $key
   *   Key in config to get value for.
   *
   * @return array|mixed|null
   *   Value from config.
   */
  private function getConfig(string $key) {
    static $config;

    if (empty($config)) {
      $config = $this->configFactory->get(LogEntityOperationsSettingsForm::CONFIG_NAME);
    }

    return $config->get($key);
  }

  /**
   * Helper function to filter out empty values from array recursively.
   *
   * @param array $input
   *   Array to filter.
   *
   * @return array
   *   Filtered array.
   */
  private function filterRecursive(array $input): array {
    foreach ($input as &$value) {
      if (is_array($value)) {
        $value = $this->filterRecursive($value);
      }
    }

    return array_filter($input);
  }

}
