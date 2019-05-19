<?php

namespace Drupal\stats\Plugin\StatDestination;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\stats\Annotation\StatDestination;
use Drupal\stats\Plugin\StatDestinationBase;
use Drupal\stats\StatExecution;
use Drupal\stats\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @StatDestination(
 *   id = "content_entity",
 *   label = "Content entity"
 * )
 */
class ContentEntity extends StatDestinationBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * ContentEntity constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\stats\StatExecution $statExecution
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, StatExecution $statExecution, EntityTypeManager $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $statExecution);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityStorage = $this->entityTypeManager->getStorage($this->configuration['entity_type']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, StatExecution $execution = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $execution,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row) {

    // Creates a new or existing entity from row values.
    $entity = $this->ensureEntity($row);

    foreach ($row->getDestination() as $field_name => $values) {
      $field = $entity->$field_name;
      if ($field instanceof TypedDataInterface) {
        $field->setValue($values);
      }
    }

    // Force a new revision, when configuration is set.
    if (!empty($this->configuration['new_revision']) && $entity instanceof RevisionableInterface) {
      $entity->setNewRevision(TRUE);
    }
    // Optionally a custom log message is provided.
    if (isset($this->configuration['revision_log_message']) && $entity instanceof RevisionLogInterface) {
      $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $entity->setRevisionLogMessage($this->configuration['revision_log_message']);
    }

    $entity->save();
  }

  /**
   * Loads existing or creates entity from row.
   *
   * @param \Drupal\stats\Row $row
   *
   * @return \Drupal\Core\Entity\ContentEntityBase|null
   */
  protected function ensureEntity(Row $row) {
    if (empty($this->configuration['loadByProperty'])) {
      return;
    }

    $properties = [];
    foreach ($this->configuration['loadByProperty'] as $property) {
      $properties[$property] = $row->getDestinationProperty($property);
    }

    $entities = $this->entityStorage->loadByProperties($properties);
    if (!empty($entities)) {
      return current($entities);
    }

    return $this->entityStorage->create($properties);
  }


}
