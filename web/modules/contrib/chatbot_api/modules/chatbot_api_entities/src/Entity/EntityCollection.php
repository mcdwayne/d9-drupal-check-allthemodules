<?php

namespace Drupal\chatbot_api_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * Defines the Entity collection entity.
 *
 * @ConfigEntityType(
 *   id = "chatbot_api_entities_collection",
 *   label = @Translation("Entity collection"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\chatbot_api_entities\EntityCollectionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\chatbot_api_entities\Form\EntityCollectionForm",
 *       "edit" = "Drupal\chatbot_api_entities\Form\EntityCollectionForm",
 *       "delete" = "Drupal\chatbot_api_entities\Form\EntityCollectionDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "chatbot_api_entities_collection",
 *   admin_permission = "administer chatbot api entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/service/chatbot-api-entity-collection/{chatbot_api_entities_collection}",
 *     "add-form" = "/admin/config/service/chatbot-api-entity-collection/add",
 *     "edit-form" = "/admin/config/service/chatbot-api-entity-collection/{chatbot_api_entities_collection}/edit",
 *     "delete-form" = "/admin/config/service/chatbot-api-entity-collection/{chatbot_api_entities_collection}/delete",
 *     "collection" = "/admin/config/service/chatbot-api-entity-collection"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "synonyms",
 *     "query_handlers",
 *     "push_handlers",
 *     "entity_type",
 *     "bundle"
 *   }
 * )
 */
class EntityCollection extends ConfigEntityBase implements EntityCollectionInterface, EntityWithPluginCollectionInterface {

  /**
   * The Entity collection ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Entity collection label.
   *
   * @var string
   */
  protected $label;

  /**
   * Field name for deriving synonyms.
   *
   * @var string
   */
  protected $synonyms;

  /**
   * Bundle for this collection.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Entity type ID for this collection.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * Query handler configurations.
   *
   * @var array
   */
  protected $query_handlers = [];

  /**
   * Push handler configurations.
   *
   * @var array
   */
  protected $push_handlers = [];

  /**
   * Query plugin collection.
   *
   * @var \Drupal\Core\Plugin\DefaultLazyPluginCollection
   */
  protected $queryHandlerCollection;

  /**
   * Push plugin collection.
   *
   * @var \Drupal\Core\Plugin\DefaultLazyPluginCollection
   */
  protected $pushHandlerCollection;

  /**
   * Constructs a new EntityCollection object.
   *
   * @param array $values
   *   Values.
   * @param string $entity_type
   *   Entity type.
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->initializePluginCollections();
  }

  /**
   * Gets array of synonyms for the given entity if applicable.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity to get synonyms for.
   *
   * @return string[]
   *   Synonyms.
   */
  public function getSynonyms(ContentEntityInterface $entity) {
    if (!$this->synonyms || !$entity->hasField($this->synonyms) || $entity->get($this->synonyms)->isEmpty()) {
      return [];
    }
    $synonyms = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
    foreach ($entity->get($this->synonyms) as $field_item) {
      $synonyms[] = $field_item->get('value')->getValue();
    }
    return $synonyms;
  }

  /**
   * Generates the collection of entities using the query handlers and pushes.
   *
   * Calls each query handler in sequence to build up a list of entities then
   * passes them to each push handler to send to the remote chatbot endpoints.
   */
  public function queryAndPush(EntityTypeManagerInterface $entityTypeManager) {
    $entities = [];
    /** @var \Drupal\chatbot_api_entities\Plugin\QueryHandlerInterface $plugin */
    foreach ($this->queryHandlerCollection as $plugin) {
      $entities = $plugin->query($entityTypeManager, $entities, $this);
    }
    if (!$entities) {
      // Nothing matched.
      return;
    }
    /** @var \Drupal\chatbot_api_entities\Plugin\PushHandlerInterface $plugin */
    foreach ($this->pushHandlerCollection as $plugin) {
      $plugin->pushEntities($entities, $this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'query_handlers' => $this->queryHandlerCollection,
      'push_handlers' => $this->pushHandlerCollection,
    ];
  }

  /**
   * Gets the entity type ID for the entities represented collection.
   *
   * @return string
   *   Entity type ID.
   */
  public function getCollectionEntityTypeId() {
    return $this->entity_type;
  }

  /**
   * Gets the bundle for entities represented by the collection.
   *
   * @return string
   *   Bundle.
   */
  public function getCollectionBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $configuration = $this->pushHandlerCollection->getConfiguration();
    /** @var \Drupal\chatbot_api_entities\Plugin\PushHandlerInterface $plugin */
    foreach ($this->pushHandlerCollection as $instance_id => $plugin) {
      $instance_configuration = [];
      if (isset($configuration[$instance_id])) {
        $instance_configuration = $configuration[$instance_id];
      }
      $instance_configuration = $plugin->saveConfiguration($this, $instance_configuration);
      $this->setPushHandlerConfiguration($instance_id, $instance_configuration);
    }
    $return = parent::save();
    // Queue it for updating.
    \Drupal::queue('chatbot_api_entities_push')->createItem([
      'collection_id' => $this->id(),
      'created' => time(),
    ]);
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryHandlerConfiguration($instance_id, array $configuration) {
    $this->query_handlers[$instance_id] = $configuration;
    if (isset($this->queryHandlerCollection)) {
      $this->queryHandlerCollection->setInstanceConfiguration($instance_id, $configuration);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPushHandlerConfiguration($instance_id, array $configuration) {
    $this->push_handlers[$instance_id] = $configuration;
    if (isset($this->pushHandlerCollection)) {
      $this->pushHandlerCollection->setInstanceConfiguration($instance_id, $configuration);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSynonymField() {
    return $this->synonyms;
  }

  /**
   * Sets up plugin collections.
   */
  protected function initializePluginCollections() {
    $this->queryHandlerCollection = new DefaultLazyPluginCollection(\Drupal::service('plugin.manager.chatbot_api_entities_query_handler'), $this->query_handlers);
    $this->pushHandlerCollection = new DefaultLazyPluginCollection(\Drupal::service('plugin.manager.chatbot_api_entities_push_handler'), $this->push_handlers);
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    $this->initializePluginCollections();
  }

}
