<?php

namespace Drupal\webfactory;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class SlaveEntityImportEvent.
 */
class EntityImportEvent extends Event {

  /**
   * Event name.
   */
  const EVENT_NAME = 'webfactory.entity.import';

  /**
   * Local Entity.
   *
   * @var EntityInterface
   */
  protected $localEntity;

  /**
   * Remote Entity datas.
   *
   * @var array
   */
  protected $remoteEntityDatas;

  /**
   * Field definitions of the Entity.
   *
   * @var array
   */
  protected $fieldDefinitions;

  /**
   * SlaveEntityImportEvent constructor.
   *
   * @param EntityInterface $local_entity
   *   Local entity to update/create.
   * @param array $remote_entity_datas
   *   Remote Entity datas to import.
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   Field definitions of the bundle.
   */
  public function __construct(EntityInterface $local_entity, array $remote_entity_datas, array $field_definitions) {
    $this->localEntity = $local_entity;
    $this->remoteEntityDatas = $remote_entity_datas;
    $this->fieldDefinitions = $field_definitions;
  }

  /**
   * Get local entity.
   *
   * @return object
   *   Entity.
   */
  public function getLocalEntity() {
    return $this->localEntity;
  }

  /**
   * Get remote entity.
   *
   * @return array
   *   Remote entity array.
   */
  public function getRemoteEntityDatas() {
    return $this->remoteEntityDatas;
  }

  /**
   * Get field definitions.
   *
   * @return array
   *   Field definitions.
   */
  public function getFieldDefinitions() {
    return $this->fieldDefinitions;
  }

}
