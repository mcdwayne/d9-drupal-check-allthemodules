<?php

namespace Drupal\staged_content\Normalizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\serialization\Normalizer\FieldableEntityNormalizerTrait;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Converts the Drupal entity object structure to a storage optimized structure.
 */
class ContentEntityNormalizer extends NormalizerBase {

  use FieldableEntityNormalizerTrait;

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Entity\ContentEntityInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    // Prevent User 0 and 1 from being normalized'.
    if ($entity->getEntityTypeId() == 'user' && in_array($entity->id(), [0, 1])) {
      return;
    }

    $attributes = [
      'meta' => [],
      'fixtures' => [],
      'data' => [],
    ];

    $context += [
      'account' => NULL,
      'included_fields' => NULL,
    ];

    $this->addMetaHeader($attributes, $entity, $context);

    $idKey = $this->entityTypeManager->getDefinition($entity->getEntityTypeId())->getKey('id');
    $excludedNames[] = $idKey;

    $revisionIdKey = $this->entityTypeManager->getDefinition($entity->getEntityTypeId())->getKey('revision');
    if (isset($revisionIdKey)) {
      $excludedNames[] = $revisionIdKey;
    }

    foreach ($entity as $name => $field_items) {
      if ($field_items->access('view', $context['account'])) {

        // We dont preserve this data in the "standard" serialized form
        // since it makes it hard to extract when denormalizing. The relevant
        // data is stored in the header.
        // @TODO Check usecases and clean this up.
        if (in_array($name, $excludedNames)) {
          continue;
        }

        $attributes['data'][$name] = $this->serializer->normalize($field_items, $format, $context);
      }
    }

    return $attributes;
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::denormalize().
   *
   * @param array $data
   *   Entity data to restore.
   * @param string $class
   *   Unused, entity_create() is used to instantiate entity objects.
   * @param string $format
   *   Format the given data was extracted from.
   * @param array $context
   *   Options available to the denormalizer. Keys that can be used:
   *   - request_method: if set to "patch" the denormalization will clear out
   *     all default values for entity fields before applying $data to the
   *     entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An unserialized entity object containing the data in $data.
   *
   * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {

    // Get type, necessary for determining which bundle to create.
    if (!isset($data['meta']['entity_type'])) {
      throw new UnexpectedValueException('The entity type should be specified.');
    }

    $dataToNormalize = [];

    // Check or the entity already exists and update if it does.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = \Drupal::service('entity.repository')->loadEntityByUuid($data['meta']['entity_type'], $data['data']['uuid'][0]['value']);

    if (!isset($entity)) {
      $values = [];
      $bundleKey = $this->entityTypeManager->getDefinition($data['meta']['entity_type'])->getKey('bundle');

      if (isset($bundleKey)) {
        $values[$bundleKey] = $data['meta']['entity_bundle'];
      }

      if ($data['meta']['preserve_original_id']) {
        // Check or the id already exists for an entity with a different uuid.
        // This happens when trying to import on an environment where new nodes
        // have been added. (sort of an edge case, but development is not
        // always linear so we'll account for it.
        $duplicate = $this->entityTypeManager->getStorage($data['meta']['entity_type'])->load($data['meta']['original_id']);
        if (!$duplicate) {
          $idKey = $this->entityTypeManager->getDefinition($data['meta']['entity_type'])->getKey('id');
          $values[$idKey] = $data['meta']['original_id'];

          $revisionKey = $this->entityTypeManager->getDefinition($data['meta']['entity_type'])->getKey('revision');
          $values[$revisionKey] = $data['meta']['original_id'];
        }
      }
      $entity = $this->entityTypeManager->getStorage($data['meta']['entity_type'])->create($values);
      $dataToNormalize = $data['data'];
    }
    else {
      // If the entity already exists we only want to update reference fields.
      // @TODO This always assumes that this is the second pass, and that the
      // initial pass has added any other data.
      foreach ($entity->getFieldDefinitions() as $fieldMachineName => $definition) {
        // For the second pass we are only interested in the reference fields.
        // And the link field (since it can hold references to other entities).
        $isReferenceField = in_array($definition->getClass(), [
          '\Drupal\Core\Field\EntityReferenceFieldItemList',
          '\Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList',
          '\Drupal\file\Plugin\Field\FieldType\FileFieldItemList',
        ]);
        $isLinkField = $definition->getItemDefinition()->getClass() == 'Drupal\link\Plugin\Field\FieldType\LinkItem';
        if (($isLinkField || $isReferenceField) && isset($data['data'][$fieldMachineName])) {
          $dataToNormalize[$fieldMachineName] = $data['data'][$fieldMachineName];
        }
      }
    }

    // Ensure that fields that have been deleted don't generate a fatal error.
    // But emit a warning instead.
    $this->prepareFieldData($dataToNormalize, $entity, $format, $context);

    $this->denormalizeFieldData($dataToNormalize, $entity, $format, $context);

    // Add a default id for the owner.
    if ($entity instanceof EntityOwnerInterface) {
      if (empty($entity->getOwnerId())) {
        $entity->setOwnerId(1);
      }
    }

    return $entity;
  }

  /**
   * Denormalizes entity data by denormalizing each field individually.
   *
   * @param array $data
   *   The data to denormalize.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The fieldable entity to set field values for.
   * @param string $format
   *   The serialization format.
   * @param array $context
   *   The context data.
   */
  protected function prepareFieldData(array &$data, FieldableEntityInterface $entity, $format, $context) {
    $unrecognizedFields = [];
    foreach ($data as $field_name => $field_data) {
      if (!$entity->hasField($field_name)) {
        $unrecognizedFields[$field_name] = $field_name;
      }
    }

    if (!empty($unrecognizedFields)) {
      // @TODO Improve logging.
      echo sprintf("Data for entity %s contains following unrecognised field names: %s . \n",
        $entity->getEntityTypeId() . ':' . $entity->uuid(),
        implode(' ,', $unrecognizedFields)
      );

      // Delete the unknown data.
      $data = array_diff_key($data, $unrecognizedFields);
    }
  }

  /**
   * Add a meta header with some extra info for debugging and reimporting.
   *
   * @param array $attributes
   *   Serialized attributes for the item.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity interface for the.
   * @param array $context
   *   Context for the serializer.
   */
  protected function addMetaHeader(array &$attributes, EntityInterface $entity, array $context) {
    $attributes['meta']['uuid'] = $entity->uuid();
    $attributes['meta']['entity_type'] = $entity->getEntityTypeId();
    $attributes['meta']['entity_bundle'] = $entity->bundle();

    $attributes['meta']['preserve_original_id'] = $this->includeId($context);
    if ($this->includeId($context)) {
      $attributes['meta']['original_id'] = $entity->id();
    }

    if ($entity instanceof EntityOwnerInterface) {
      // Both the admin 1 user and the anonymous (0) user are generated at
      // install time. We'll flag such content here so the importer can handle
      // this accordingly.
      if (in_array($entity->getOwnerId(), [0, 1])) {
        $attributes['meta']['preexisting_owner'] = TRUE;
        $attributes['meta']['preexisting_owner_id'] = $entity->getOwnerId();
      }
    }
  }

}
