<?php

namespace Drupal\representative_image;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\representative_image\Exception\RepresentativeImageFieldNotDefinedException;

/**
 * Finds representative image fields on entities.
 */
class RepresentativeImagePicker {
  use StringTranslationTrait;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a RepresentativeImagePicker object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Gets the representative image field item from a representative image field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   A field item list containing a representative image field.
   *
   * @throws \LogicException
   *   Thrown when $items does not contain a representative image field.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   */
  public function getImageFieldItemList(FieldItemListInterface $items) {
    if ($items->getFieldDefinition()->getType() != 'representative_image') {
      throw new \LogicException('Items does not contain a representative image field.');
    }

    $field_settings = $items->getFieldDefinition()->getSettings();
    $field_name = $field_settings['representative_image_field_name'];

    $entity = $items->getEntity();
    if (!empty($field_name) && !$entity->get($field_name)->isEmpty()) {
      $image_field_items = $entity->get($field_name);
    }
    else {
      $behavior = $field_settings['representative_image_behavior'];
      if ($behavior == 'nothing') {
        return NULL;
      }
      else {
        if ($behavior == 'first') {
          $image_field_items = $this->getFirstAvailableImageField($entity);
        }
        else {
          $image_field_items = $this->getDefaultImage($items);
        }
      }
    }

    // We got a field item, but it's a reference to another entity such as a
    // media entity.
    $image_field_items = $this->getImageFieldFromReference($image_field_items);

    return $image_field_items;
  }

  /**
   * Returns the first image field containing an image in the entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity being viewed.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   *   The field item or NULL if not found.
   */
  protected function getFirstAvailableImageField(FieldableEntityInterface $entity) {
    $field_item = NULL;
    foreach ($this->getSupportedFields($entity->getEntityTypeId(), $entity->bundle()) as $field_id => $field_label) {
      if (!$entity->get($field_id)->isEmpty()) {
        $field_item = $entity->get($field_id);
        break;
      }
    }

    return $field_item;
  }

  /**
   * Finds supported image fields to use as representative field.
   *
   * Supported fields include image fields (which themselves are entity
   * references to file entities), or generic entity reference fields. In this
   * case, it is expected that the referenced entity type has it's own
   * representative image field configured.
   *
   * @param string $entity_type
   *   The entity type name.
   * @param string $bundle
   *   The bundle name.
   *
   * @return array
   *   An associative array with field id as keys and field labels as values.
   */
  public function getSupportedFields($entity_type, $bundle) {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    $options = [];
    foreach ($field_definitions as $field_id => $field_definition) {
      if (in_array($field_definition->getType(), ['image', 'entity_reference']) && $field_definition instanceof ListDataDefinitionInterface) {
        $target_entity_type = $field_definition->getItemDefinition()->getSetting('target_type');
        $definition = \Drupal::entityTypeManager()->getDefinition($target_entity_type);
        if (is_a($definition->getClass(), ContentEntityInterface::class, TRUE)) {
          $options[$field_id] = $this->t(':label (@id)', [':label' => $field_definition->getConfig($bundle)->label(), '@id' => $field_id]);
        }
      }
    }

    return $options;
  }

  /**
   * Returns the default field image.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The item list.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   *   The field item list or NULL if not found.
   */
  protected function getDefaultImage(FieldItemListInterface $items) {
    $default_image = $items->getFieldDefinition()->getSetting('default_image');
    // If we are dealing with a configurable field, look in both
    // instance-level and field-level settings.
    if (empty($default_image['uuid']) && $items->getFieldDefinition() instanceof FieldConfigInterface) {
      $default_image = $items->getFieldDefinition()->getFieldStorageDefinition()->getSetting('default_image');
    }
    if (!empty($default_image['uuid']) && $file = \Drupal::entityManager()->loadEntityByUuid('file', $default_image['uuid'])) {
      // Clone the FieldItemList into a runtime-only object for the formatter,
      // so that the fallback image can be rendered without affecting the
      // field values in the entity being rendered.
      $items = clone $items;
      $items->setValue([
        'target_id' => $file->id(),
        'alt' => $default_image['alt'],
        'title' => $default_image['title'],
        'width' => $default_image['width'],
        'height' => $default_image['height'],
        'entity' => $file,
        '_loaded' => TRUE,
        '_is_default' => TRUE,
      ]);
    }

    return $items;
  }

  /**
   * Find a representative image field by following entity references.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $list
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   */
  protected function getImageFieldFromReference(FieldItemListInterface $list) {
    if (!$this->fieldItemIsEntityReference($list)) {
      return $list;
    }

    if ($list instanceof EntityReferenceFieldItemListInterface) {
      /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
      foreach ($list->referencedEntities() as $entity) {
        if ($this->hasRepresentativeImageField($entity)) {
          $representative_field = $this->getRepresentativeImageField($entity);
          return $this->getImageFieldItemList($representative_field);
        }
      }
    }

    return NULL;
  }

  /**
   * Return if the field is not an image field and is not empty.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field_item
   *   The field item to check.
   *
   * @return bool
   *   TRUE if the field is not an image field and has a value.
   */
  private function fieldItemIsEntityReference(FieldItemListInterface $field_item): bool {
    return ($field_item instanceof EntityReferenceFieldItemListInterface) && $field_item->getFieldDefinition()->getType() != 'image' && !$field_item->isEmpty();
  }

  /**
   * Return if the entity has a representative image field defined.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to find the representative image field on.
   *
   * @return bool
   *   TRUE if the field is defined, false otherwise.
   */
  public function hasRepresentativeImageField(FieldableEntityInterface $entity) {
    try {
      $this->getRepresentativeImageField($entity);
      return TRUE;
    }
    catch (RepresentativeImageFieldNotDefinedException $e) {
      // Fall through to the FALSE return.
    }
    return FALSE;
  }

  /**
   * Finds the representative image field in an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   An entity instance.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   The representative image field.
   */
  public function getRepresentativeImageField(FieldableEntityInterface $entity) {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    foreach ($field_definitions as $field_id => $field_definition) {
      if ($field_definition->getType() == 'representative_image') {
        return $entity->get($field_id);
      }
    }

    throw new RepresentativeImageFieldNotDefinedException();
  }

}
