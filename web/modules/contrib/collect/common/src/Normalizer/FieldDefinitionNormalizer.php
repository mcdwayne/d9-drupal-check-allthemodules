<?php

/**
 * @file
 * Contains \Drupal\collect_common\Normalizer\FieldDefinitionNormalizer.
 */

namespace Drupal\collect_common\Normalizer;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Converts the Drupal field definition structure to array structure.
 */
class FieldDefinitionNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Field\FieldDefinitionInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    /* @var \Drupal\Core\Field\FieldDefinitionInterface $object */
    $storage_definition = $object->getFieldStorageDefinition();

    /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager */
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');

    // The field definition is commonly of type BaseFieldDefinition, where field
    // settings and field storage settings are merged in one array, and both are
    // returned by getSettings(). Through the default settings, we can identify
    // which elements of getSettings() are field settings and which are field
    // storage settings.
    $default_field_settings = $field_type_manager->getDefaultFieldSettings($object->getType());
    $default_storage_settings = $field_type_manager->getDefaultStorageSettings($object->getFieldStorageDefinition()->getType());

    return array(
      'type' => 'field_item',
      'field_type' => $object->getType(),
      'field_name' => $object->getName(),
      'entity_type' => $object->getTargetEntityTypeId(),
      'bundle' => $object->getTargetBundle() ?: $object->getTargetEntityTypeId(),
      // Render TranslationWrapper objects as strings.
      'label' => (string) $object->getLabel(),
      'description' => (string) $object->getDescription(),
      'required' => $object->isRequired(),
      'translatable' => $object->isTranslatable(),
      'settings' => $field_settings = array_intersect_key($object->getSettings(), $default_field_settings),
      'storage' => array(
        'cardinality' => $storage_definition->getCardinality(),
        'custom_storage' => $storage_definition->hasCustomStorage(),
        'field_name' => $storage_definition->getName(),
        'provider' => $storage_definition->getProvider(),
        'queryable' => $storage_definition->isQueryable(),
        'revisionable' => $storage_definition->isRevisionable(),
        'settings' => $field_storage_settings = array_intersect_key($object->getSettings(), $default_storage_settings),
        'entity_type' => $storage_definition->getTargetEntityTypeId(),
        'translatable' => $storage_definition->isTranslatable(),
        'type' => $storage_definition->getType(),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    $storage = $data['storage'];
    // BaseFieldDefinition is a field definition and a field storage definition
    // at the same time.
    return BaseFieldDefinition::create($data['field_type'])
      // Field properties.
      ->setDescription($data['description'])
      ->setLabel($data['label'])
      ->setRequired($data['required'])
      ->setTargetBundle($data['bundle'])
      // Shared properties.
      ->setName($data['field_name'])
      ->setTargetEntityTypeId($data['entity_type'])
      // Field storage properties.
      ->setCardinality($storage['cardinality'])
      ->setCustomStorage($storage['custom_storage'])
      ->setProvider($storage['provider'])
      ->setQueryable($storage['queryable'])
      ->setRevisionable($storage['revisionable'])
      ->setSettings($storage['settings'])
      ->setTranslatable($storage['translatable']);
  }

}
