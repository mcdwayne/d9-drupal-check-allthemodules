<?php

namespace Drupal\imagepin;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\imagepin\Plugin\WidgetInterface;
use Drupal\imagepin\Plugin\WidgetManager;

/**
 * The repository of widgets.
 */
class WidgetRepository {

  /**
   * The database connection instance.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The serializer instance.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The WidgetManager instance.
   *
   * @var \Drupal\imagepin\Plugin\WidgetManager
   */
  protected $widgetManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The EntityTypeManagerInterface instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor method.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection instance.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serializer instance.
   * @param \Drupal\imagepin\Plugin\WidgetManager $widget_manager
   *   The WidgetManager instance.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManagerInterface instance.
   */
  public function __construct(Connection $connection, SerializationInterface $serializer, WidgetManager $widget_manager, AccountProxy $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $connection;
    $this->serializer = $serializer;
    $this->widgetManager = $widget_manager;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Saves the given widget value.
   *
   * @param \Drupal\imagepin\Plugin\WidgetInterface $widget
   *   An instance of the widget plugin.
   * @param mixed $value
   *   The widget value. Because the value will be stored in the database,
   *   make sure the value is serializable.
   * @param array $belonging
   *   An array, whose keys define the belonging entity of this value.
   *   Consists of following keys:
   *     - entity_type: The entity type id as string.
   *     - bundle: The bundle of the entity.
   *     - entity_id: The entity id, can be NULL for new entities.
   *     - language: The language of the entity.
   *     - field_name: The name of the field,
   *                   where the corresponding image is being referenced.
   *     - image_fid: The file id of the corresponding image.
   *     - view_mode: The corresponding view mode of the entity.
   * @param int $key
   *   A given key indicates this value should update an existing widget record.
   */
  public function save(WidgetInterface $widget, $value, array $belonging, $key = NULL) {
    // Allow the plugin to prepare its value data.
    $widget->preSave($value, $belonging, $key);

    $fields = [
      'widget_type' => $widget->getPluginId(),
      'widget_value' => $this->serializer->encode($value),
      'user' => $this->currentUser->id(),
    ] + $belonging;
    if ($key) {
      $this->database->update('imagepin_widgets')
        ->fields($fields)
        ->where('widget_key = :key', [':key' => $key])
        ->execute();
    }
    else {
      $this->database->insert('imagepin_widgets')
        ->fields($fields)->execute();
    }

    // Clear the render cache of the corresponding entity.
    if (isset($belonging['entity_type'], $belonging['entity_id'])) {
      $this->clearRenderCache($belonging['entity_type'], $belonging['entity_id']);
    }
  }

  /**
   * Loads all available widget instances for a certain image of an entity.
   *
   * @param array $belonging
   *   An array, whose keys define the belonging entity of this value.
   *   See ::save() for the keys of this array.
   *
   * @return array
   *   A list of available widgets, keyed by widget key. Its array value is
   *   an array, keyed by widget 'plugin' and corresponding widget 'value'.
   *   The 'plugin' is a Drupal\imagepin\Plugin\WidgetInterface,
   *   whereas the 'value' can be any arbitrary data type, depending on its
   *   implementation.
   */
  public function loadBelonging(array $belonging) {
    $query = $this->database->select('imagepin_widgets', 'widgets')
      ->fields('widgets', ['widget_type', 'widget_key', 'widget_value']);
    $query->where('entity_type = :type', [':type' => $belonging['entity_type']]);
    $query->where('bundle = :bundle', [':bundle' => $belonging['bundle']]);
    $query->where('language = :language', [':language' => $belonging['language']]);
    $query->where('field_name = :field_name', [':field_name' => $belonging['field_name']]);
    $query->where('image_fid = :fid', [':fid' => $belonging['image_fid']]);
    $query->where('view_mode = :mode', [':mode' => $belonging['view_mode']]);
    if (isset($belonging['entity_id'])) {
      $query->where('entity_id = :id', [':id' => $belonging['entity_id']]);
    }
    else {
      $query->where('entity_id IS NULL');
    }

    $records = $query->execute();
    $widgets = [];
    foreach ($records as $record) {
      $widgets[$record->widget_key] = [
        'key' => $record->widget_key,
        'value' => $this->serializer->decode($record->widget_value),
        'plugin' => $this->widgetManager->createInstance($record->widget_type),
      ];
    }

    return $widgets;
  }

  /**
   * Loads all available widget instances for the given entity field view.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being viewed.
   * @param string $field_name
   *   The machine name of the field being viewed.
   * @param string $view_mode
   *   The view mode in which this field is being rendered.
   *
   * @return array
   *   A list of widgets, grouped by image fid. See ::loadBelonging()
   *   how the widget arrays are structured.
   */
  public function loadForEntityFieldView(EntityInterface $entity, $field_name, $view_mode) {
    $query = $this->database->select('imagepin_widgets', 'widgets')
      ->fields('widgets', ['image_fid', 'widget_type', 'widget_key', 'widget_value']);
    $query->where('entity_type = :type', [':type' => $entity->getEntityTypeId()]);
    $query->where('entity_id = :id', [':id' => $entity->id()]);
    $query->where('language = :language', [':language' => $entity->language()->getId()]);
    $query->where('field_name = :field_name', [':field_name' => $field_name]);
    $query->where('view_mode = :mode', [':mode' => $view_mode]);

    $records = $query->execute();
    $widgets = [];
    foreach ($records as $record) {
      $widgets[$record->image_fid][$record->widget_key] = [
        'key' => $record->widget_key,
        'value' => $this->serializer->decode($record->widget_value),
        'plugin' => $this->widgetManager->createInstance($record->widget_type),
      ];
    }

    return $widgets;
  }

  /**
   * Loads a single widget instance by given key.
   *
   * @param int $widget_key
   *   The unique widget key.
   *
   * @return array
   *   An array of the instance, with following keys:
   *     - key: The widget key.
   *     - value: The value of the widget instance.
   *     - plugin: Drupal\imagepin\Plugin\WidgetInterface
   */
  public function load($widget_key) {
    $query = $this->database->select('imagepin_widgets', 'widgets')
      ->fields('widgets', ['widget_type', 'widget_key', 'widget_value']);
    $query->where('widget_key = :key', [':key' => $widget_key]);
    $record = $query->execute()->fetchObject();
    return [
      'key' => $record->widget_key,
      'value' => $this->serializer->decode($record->widget_value),
      'plugin' => $this->widgetManager->createInstance($record->widget_type),
    ];
  }

  /**
   * Permanently removes the widget record by the given key from the database.
   *
   * @param int $widget_key
   *   The unique widget key.
   */
  public function delete($widget_key) {
    $query = $this->database->select('imagepin_widgets', 'widgets')
      ->fields('widgets', ['entity_type', 'entity_id']);
    $query->where('widget_key = :key', [':key' => $widget_key]);
    $record = $query->execute()->fetchObject();
    if ($record) {
      $this->database->delete('imagepin_widgets')
        ->where('widget_key = :key', [':key' => $widget_key])
        ->execute();

      // Clear the render cache of the corresponding entity.
      $this->clearRenderCache($record->entity_type, $record->entity_id);
    }
  }

  /**
   * Permanently removes all widget records by the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $langcode
   *   (Optional) When given, only all widget records
   *   for this language will be deleted.
   */
  public function deleteAllByEntity(EntityInterface $entity, $langcode = NULL) {
    $type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $id = $entity->id();

    $statement = $this->database->delete('imagepin_widgets');
    $statement->where('entity_type = :type', [':type' => $type]);
    $statement->where('bundle = :bundle', [':bundle' => $bundle]);
    $statement->where('entity_id = :id', [':id' => $id]);
    if (!empty($langcode)) {
      $statement->where('language = :langcode', [':langcode' => $langcode]);
    }
    $statement->execute();

    // Clear the render cache of the corresponding entity.
    $this->clearRenderCache($entity->getEntityTypeId(), $entity->id());
  }

  /**
   * Adopts all widget records, which were attached to a new entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function adoptFromNew(EntityInterface $entity) {
    $type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $langcode = $entity->language()->getId();
    $id = $entity->id();

    $select = $this->database
      ->select('imagepin_widgets', 'widgets')->fields('widgets');
    $select->where('entity_type = :type', [':type' => $type]);
    $select->where('bundle = :bundle', [':bundle' => $bundle]);
    $select->where('language = :langcode', [':langcode' => $langcode]);
    $select->where('user = :uid', [':uid' => $this->currentUser->id()]);

    // Make sure this entity hasn't adopted some widgets yet.
    $existing = clone $select;
    $existing->where('entity_id = :id', [':id' => $id])->range(0, 1);
    $existing = $existing->execute()->fetchObject();
    if (!empty($existing)) {
      return;
    }

    $attached_new = clone $select;
    $attached_new->where('entity_id IS NULL');
    $attached_new = $attached_new->execute();
    while ($to_adopt = $attached_new->fetchAssoc()) {
      $widget = $this->widgetManager->createInstance($to_adopt['widget_type']);
      $key = $to_adopt['widget_key'];
      $value = $this->serializer->decode($to_adopt['widget_value']);
      $to_adopt['entity_id'] = $entity->id();
      unset($to_adopt['user'], $to_adopt['widget_type'], $to_adopt['widget_key'], $to_adopt['widget_value']);
      $this->save($widget, $value, $to_adopt, $key);
    }
  }

  /**
   * Helper function to clear an entity's render cache.
   *
   * @param string $type
   *   The entity type Id.
   * @param int|string $id
   *   The entity id as integer or string.
   */
  protected function clearRenderCache($type, $id) {
    $entity = $this->entityTypeManager->getStorage($type)->load($id);
    try {
      $view_builder = $this->entityTypeManager->getViewBuilder($type);
    }
    catch (InvalidPluginDefinitionException $e) {
      return;
    }
    if ($entity && $view_builder) {
      $view_builder->resetCache([$entity]);
    }
  }

}
