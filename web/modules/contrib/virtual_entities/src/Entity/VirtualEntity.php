<?php

namespace Drupal\virtual_entities\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\virtual_entities\VirtualEntityInterface;

/**
 * Defines the Virtual entity entity.
 *
 * @ingroup virtual_entities
 *
 * @ContentEntityType(
 *   id = "virtual_entity",
 *   label = @Translation("Virtual entity"),
 *   label_collection = @Translation("Virtual entity"),
 *   label_singular = @Translation("Virtual entity"),
 *   label_plural = @Translation("Virtual entities"),
 *   label_count = @PluralTranslation(
 *     singular = "@count virtual entity",
 *     plural = "@count virtual entities"
 *   ),
 *   bundle_label = @Translation("Virtual entity type"),
 *   handlers = {
 *     "storage" = "Drupal\virtual_entities\VirtualEntityStorage",
 *     "storage_schema" = "Drupal\virtual_entities\VirtualEntityStorageSchema",
 *
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\virtual_entities\VirtualEntityListBuilder",
 *     "views_data" = "Drupal\virtual_entities\Entity\VirtualEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\virtual_entities\Form\VirtualEntityForm",
 *       "add" = "Drupal\virtual_entities\Form\VirtualEntityForm",
 *       "edit" = "Drupal\virtual_entities\Form\VirtualEntityForm",
 *       "delete" = "Drupal\virtual_entities\Form\VirtualEntityDeleteForm",
 *     },
 *     "access" = "Drupal\virtual_entities\VirtualEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\virtual_entities\VirtualEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "virtual_entity",
 *   admin_permission = "administer virtual entity entities",
 *   static_cache = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/virtual-entity/{virtual_entity}",
 *     "add-form" = "/virtual-entity/add",
 *     "delete-form" = "/virtual-entity/{virtual_entity}/delete",
 *     "edit-form" = "/virtual-entity/{virtual_entity}/edit",
 *     "collection" = "/admin/content/virtual_entity",
 *   },
 *   bundle_entity_type = "virtual_entity_type",
 *   field_ui_base_route = "entity.virtual_entity_type.edit_form"
 * )
 */
class VirtualEntity extends ContentEntityBase implements VirtualEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function virtualId() {
    return virtual_entities_hash(parent::id());
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return self::getType() . '-' . self::virtualId();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Unique ID'))
      ->setDescription(t('The ID of the Virtual entity entity.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The Virtual entity type/bundle.'))
      ->setSetting('target_type', 'virtual_entity_type')
      ->setRequired(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Virtual entity entity.'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The name of the Virtual entity entity.'))
      ->setSetting('max_length', 255)
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (method_exists($storage, 'preSave')) {
      $storage->preSave($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    if (method_exists($storage, 'preDelete')) {
      $storage->preDelete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMappedObject() {
    $bundle = $this->entityManager()->getStorage('virtual_entity_type')->load($this->bundle());
    $object = new \stdClass();
    foreach ($bundle->getFieldMappings() as $source => $destination) {
      $field_definition = $this->getFieldDefinition($source);
      $settings = $field_definition->getSettings();
      $property = $field_definition->getFieldStorageDefinition()->getMainPropertyName();

      $offset = 0;
      // Special case for references to virtual entities.
      if (isset($settings['target_type']) && $settings['target_type'] === 'virtual_entity') {
        // Only 1 bundle is allowed.
        $target_bundle = reset($settings['handler_settings']['target_bundles']);
        $offset = strlen($target_bundle) + 1;
      }
      // If the field has many item we process each one.
      if ($this->get($source)->count() > 1) {
        $values = $this->get($source)->getValue();
        $object->{$destination} = [];
        foreach ($values as $value_row) {
          $object->{$destination}[] = substr($value_row[$property], $offset);
        }
      }
      else {
        $object->{$destination} = substr($this->get($source)->{$property}, $offset);
      }
    }

    return $object;
  }

  /**
   * {@inheritdoc}
   */
  public function mapObject(\stdClass $obj) {
    // Don't touch the original object.
    $object = clone $obj;
    $bundle = $this->entityManager()->getStorage('virtual_entity_type')->load($this->bundle());

    foreach ($bundle->getFieldMappings() as $destination => $source) {
      $field_definition = $this->getFieldDefinition($destination);
      // When there is no definition go to the next item.
      if (!$field_definition) {
        continue;
      }
      $settings = $field_definition->getSettings();
      $property = $field_definition->getFieldStorageDefinition()->getMainPropertyName();

      $value_prefix = '';
      // Special case for references to external entities.
      if (isset($settings['target_type']) && $settings['target_type'] === 'virtual_entity') {
        // Only 1 bundle is allowed.
        $target_bundle = reset($settings['handler_settings']['target_bundles']);
        $value_prefix = $target_bundle . '-';
      }
      // Array of value for the entity.
      $destination_value = [];
      // Set at least an empty string for the destination.
      $object->{$source} = isset($object->{$source}) ? $object->{$source} : '';
      // Convert to array.
      if (!is_array($object->{$source})) {
        $object->{$source} = [$object->{$source}];
      }
      foreach ($object->{$source} as $value) {
        // For array cases we assume the property keys arrive from the client
        // correctly.
        if (is_array($value)) {
          $destination_value[] = $value;
        }
        else {
          $destination_value[] = [$property => $value_prefix . $value];
        }
      }
      $this->set($destination, $destination_value);
    }

    return $this;
  }

}
