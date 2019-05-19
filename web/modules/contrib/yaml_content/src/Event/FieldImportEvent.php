<?php

namespace Drupal\yaml_content\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\yaml_content\ContentLoader\ContentLoaderInterface;

/**
 * Wraps a yaml content field import event for event listeners.
 */
class FieldImportEvent extends DataImportEvent {

  /**
   * The entity being populated with field data.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The field defnition for the field being populated.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * Constructs a yaml content field import event object.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoaderInterface $loader
   *   The active Content Loader that triggered the event.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being populated with field data.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition for the field being populated.
   * @param array $content_data
   *   The parsed content loaded from the content file to be loaded into
   *   the entity field.
   */
  public function __construct(ContentLoaderInterface $loader, EntityInterface $entity, FieldDefinitionInterface $field_definition, array $content_data) {
    parent::__construct($loader, $content_data);

    $this->entity = $entity;
    $this->fieldDefinition = $field_definition;
  }

  /**
   * Gets the entity being populated with field data.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity being populated with field data.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Gets the field definition object for the field being populated.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition for the field being populated.
   */
  public function getFieldDefinition() {
    return $this->fieldDefinition;
  }

}
