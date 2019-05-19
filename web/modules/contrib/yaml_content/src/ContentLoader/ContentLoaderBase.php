<?php

namespace Drupal\yaml_content\ContentLoader;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\yaml_content\Event\ContentParsedEvent;
use Drupal\yaml_content\Event\EntityImportEvent;
use Drupal\yaml_content\Event\EntityPreSaveEvent;
use Drupal\yaml_content\Event\EntityPostSaveEvent;
use Drupal\yaml_content\Event\YamlContentEvents;
use Drupal\yaml_content\Event\FieldImportEvent;
use Drupal\yaml_content\Event\PreImportEvent;
use Drupal\yaml_content\Event\PostImportEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A base ContentLoader implementation to be extended by new content loaders.
 *
 * @see \Drupal\yaml_content\ContentLoader\ContentLoaderInterface
 *
 * @todo Extend this class as an EntityLoader to support later support options.
 * @todo Implement LoggerAwareTrait with logging.
 */
abstract class ContentLoaderBase implements ContentLoaderInterface {

  /**
   * Content file parser utility.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $parser;

  /**
   * Entity type manager service to dynamically handle entities.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Event dispatcher service to report events throughout the loading process.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The array of content parsed from the content file being loaded.
   *
   * @var array
   */
  protected $parsedContent;

  /**
   * The file path where content files should be loaded from.
   *
   * @var string
   */
  protected $path;

  /**
   * The file path for the content file currently being loaded.
   *
   * @var string
   */
  protected $contentFile;

  /**
   * Constructs a ContentLoaderBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   An Entity Type Manager service implementation.
   * @param \Drupal\Component\Serialization\SerializationInterface $parser
   *   A serialization parser to interpret content from content files.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   An event dispatcher service to publish events throughout the process.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, SerializationInterface $parser, EventDispatcherInterface $dispatcher) {
    $this->parser = $parser;
    $this->entityTypeManager = $entityTypeManager;
    $this->dispatcher = $dispatcher;
  }

  /**
   * Set a path prefix for all content files to be loaded from.
   *
   * @param string $path
   *   The directory path containing content files to be loaded.
   */
  public function setContentPath($path) {
    $this->path = $path;
  }

  /**
   * {@inheritdoc}
   */
  public function parseContent($content_file) {
    // @todo Handle parsing failures.
    $this->contentFile = $this->path . '/content/' . $content_file;
    $this->parsedContent = $this->parser->decode(file_get_contents($this->contentFile));

    // Never leave this as null, even on a failed parsing process.
    // @todo Output a warning for empty content files or failed parsing.
    $this->parsedContent = isset($this->parsedContent) ? $this->parsedContent : [];

    // Dispatch the event notification.
    $content_parsed_event = new ContentParsedEvent($this, $this->contentFile, $this->parsedContent);
    $this->dispatcher->dispatch(YamlContentEvents::CONTENT_PARSED, $content_parsed_event);

    return $this->parsedContent;
  }

  /**
   * {@inheritdoc}
   */
  public function loadContentBatch(array $files, array $options = []) {
    // @todo Process options.
    // Dispatch the pre-import event.
    $pre_import_event = new PreImportEvent($this, $files);
    $this->dispatcher->dispatch(YamlContentEvents::PRE_IMPORT, $pre_import_event);

    $loaded_content = [];
    foreach ($files as $file) {
      $content_data = $this->parseContent($file);

      $loaded_content[$file] = $this->loadContent($content_data);
    }

    // Dispatch the post-import event.
    $post_import_event = new PostImportEvent($this, $files, $loaded_content);
    $this->dispatcher->dispatch(YamlContentEvents::POST_IMPORT, $post_import_event);

    // @todo Reset import options.

    return $loaded_content;
  }

  /**
   * {@inheritdoc}
   */
  public function loadContent(array $content_data) {
    // Create each entity defined in the yaml content.
    $loaded_content = [];
    foreach ($content_data as $content_item) {
      $entity = $this->importEntity($content_item);

      // Dispatch the pre-save event.
      $entity_pre_save_event = new EntityPreSaveEvent($this, $entity, $content_item);
      $this->dispatcher->dispatch(YamlContentEvents::ENTITY_PRE_SAVE, $entity_pre_save_event);

      // Save the entity.
      $entity->save();

      // Dispatch the post-save event.
      $entity_post_save_event = new EntityPostSaveEvent($this, $entity, $content_item);
      $this->dispatcher->dispatch(YamlContentEvents::ENTITY_POST_SAVE, $entity_post_save_event);

      $loaded_content[] = $entity;
    }

    return $loaded_content;
  }

  /**
   * Load an entity from a loaded import data outline.
   *
   * @param array $content_data
   *   The loaded array of content data to populate into this entity.
   *
   *   Required keys:
   *     - `entity`: The entity type machine name.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The built and imported content entity.
   *
   * @throws \Exception
   */
  public function importEntity(array $content_data) {
    // @todo Validate entity information for building.
    if (!isset($content_data['entity'])) {
      throw new \Exception('An entity type is required in the "entity" key.');
    }
    else {
      $entity_type = $content_data['entity'];
    }

    if (!$this->entityTypeManager->hasDefinition($entity_type)) {
      // @todo Update this to use `t()`.
      throw new \Exception(sprintf('Invalid entity type: %s', $entity_type));
    }

    // Build the basic entity structure.
    $entity = $this->buildEntity($entity_type, $content_data);

    // @todo Break this out into `$this->importEntityFields()`.
    // Import the entity fields if applicable.
    if ($entity instanceof FieldableEntityInterface) {

      $field_definitions = $entity->getFieldDefinitions();

      // Iterate across each field value in the import content.
      foreach (array_intersect_key($content_data, $field_definitions) as $field_name => $field_data) {
        // Ensure data is wrapped as an array to handle field values as a list.
        if (!is_array($field_data)) {
          $field_data = [$field_data];
        }

        // Dispatch field import event prior to populating fields.
        $field_import_event = new FieldImportEvent($this, $entity, $field_definitions[$field_name], $field_data);
        $this->dispatcher->dispatch(YamlContentEvents::IMPORT_FIELD, $field_import_event);

        $this->importEntityField($field_data, $entity, $field_definitions[$field_name]);
      }
    }

    return $entity;
  }

  /**
   * Process import data into an appropriate field value and assign it.
   *
   * @param array $field_data
   *   Field data read from the content file for import.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The pre-built entity object being populated with field data.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition for the field being populated.
   */
  public function importEntityField(array $field_data, EntityInterface $entity, FieldDefinitionInterface $field_definition) {
    // Iterate over each field value.
    foreach ($field_data as $field_item) {
      $field_value = $this->importFieldItem($field_item, $entity, $field_definition);

      // Assign or append field item value.
      $this->assignFieldValue($entity, $field_definition->getName(), $field_value);
    }
  }

  /**
   * Process import data for an individual field list item value.
   *
   * @param array|string $field_item_data
   *   Field data for the individual field item as read from the content
   *   file for import.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The pre-built entity object being populated with field data.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition for the field being populated.
   *
   * @return mixed
   *   The processed field item value for storage in the field.
   */
  public function importFieldItem($field_item_data, EntityInterface $entity, FieldDefinitionInterface $field_definition) {
    // Is it an entity reference?
    if (is_array($field_item_data) && isset($field_item_data['entity'])) {
      $item_value = $this->importEntity($field_item_data);
    }
    else {
      $item_value = $field_item_data;
    }

    return $item_value;
  }

  /**
   * Build an entity from the provided content data.
   *
   * @param string $entity_type
   *   The machine name for the entity type being created.
   * @param array $content_data
   *   Parameters:
   *     - `entity`: (required) The entity type machine name.
   *     - `bundle`: (required) The bundle machine name.
   *     - Additional field and property data keyed by field or property name.
   * @param array $context
   *   Contextual data available for more specific entity creation requirements.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A built and populated entity object containing the imported data.
   */
  public function buildEntity($entity_type, array $content_data, array &$context = []) {
    // Load entity type definition.
    $entity_definition = $this->entityTypeManager->getDefinition($entity_type);
    $entity_keys = $entity_definition->getKeys();

    // Load entity type handler.
    $entity_handler = $this->entityTypeManager->getStorage($entity_type);

    // Dispatch the entity import event.
    $entity_import_event = new EntityImportEvent($this, $entity_definition, $content_data);
    $this->dispatcher->dispatch(YamlContentEvents::IMPORT_ENTITY, $entity_import_event);

    // Map generic entity keys into entity-specific values.
    $properties = [];
    foreach ($entity_keys as $source => $target) {
      if (isset($content_data[$source])) {
        $properties[$target] = $content_data[$source];
      }
      elseif (isset($content_data[$target])) {
        $properties[$target] = $content_data[$target];
      }
    }

    // Create the entity.
    $entity = $entity_handler->create($properties);

    return $entity;
  }

  /**
   * Set or assign a field value based on field cardinality.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   An entity object being assigned a field value.
   * @param string $field_name
   *   The machine name of the field being populated.
   * @param mixed $value
   *   The value being assigned into the entity field.
   */
  public function assignFieldValue(FieldableEntityInterface $entity, $field_name, $value) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $field */
    $field = $entity->$field_name;

    // Get the field cardinality to determine whether or not a value should be
    // 'set' or 'appended' to.
    $cardinality = $field->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getCardinality();

    // If the cardinality is 0, throw an exception.
    if (!$cardinality) {
      throw new \InvalidArgumentException("'{$field->getName()}' cannot hold any values.");
    }

    // If the cardinality is set to 1, set the field value directly.
    if ($cardinality == 1) {
      $field->setValue($value);
    }
    else {
      $field->appendItem($value);
    }
  }

}
