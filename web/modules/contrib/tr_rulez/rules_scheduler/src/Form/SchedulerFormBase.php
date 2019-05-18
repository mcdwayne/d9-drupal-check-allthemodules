<?php

namespace Drupal\rules_scheduler\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\rules\Core\RulesActionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base Form for scheduler tasks.
 */
abstract class SchedulerFormBase extends FormBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * The config entity storage that holds Rules components.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $componentStorage;

  /**
   * The Rules action plugin manager.
   *
   * @var \Drupal\rules\Core\RulesActionManagerInterface
   */
  protected $actionManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   *   The typed data manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $componentStorage
   *   The config entity storage that holds Rules components.
   * @param \Drupal\rules\Core\RulesActionManagerInterface $actionManager
   *   The Rules action plugin manager.
   */
  public function __construct(Connection $database, TypedDataManagerInterface $typedDataManager, EntityStorageInterface $componentStorage, RulesActionManagerInterface $actionManager) {
    $this->database = $database;
    $this->typedDataManager = $typedDataManager;
    $this->componentStorage = $componentStorage;
    $this->actionManager = $actionManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('typed_data_manager'),
      $container->get('entity_type.manager')->getStorage('rules_component'),
      $container->get('plugin.manager.rules_action')
    );
  }

  /**
   * Returns a typed data object.
   *
   * This is a helper for quick creation of typed data objects.
   *
   * @param string $data_type
   *   The data type to create an object for.
   * @param mixed $value
   *   The value to set.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The created object.
   */
  protected function getTypedData($data_type, $value) {
    $definition = $this->typedDataManager->createDataDefinition($data_type);
    $data = $this->typedDataManager->create($definition);
    $data->setValue($value);
    return $data;
  }

}
