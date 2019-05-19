<?php

namespace Drupal\staged_content\Normalizer;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer as BaseEntityReferenceFieldItemNormalizer;
use Doctrine\Instantiator\Exception\UnexpectedValueException;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\serialization\Normalizer\FieldableEntityNormalizerTrait;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

/**
 * Converts the Drupal entity object structure to a storage structure.
 */
class EntityReferenceFieldItemNormalizer extends BaseEntityReferenceFieldItemNormalizer {

  use FieldableEntityNormalizerTrait;

  /**
   * {@inheritdoc}
   */
  protected $format = ['storage_json'];

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem';

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The target entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   *   The target entity.
   */
  protected $targetEntity;

  /**
   * Constructs a EntityReferenceFieldItemNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityRepositoryInterface $entity_repository) {
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {

    $values = [];

    // Differentiate between config entities and content entities.
    // Since config entities can pass by pretty much unnoticed.
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $field_item->get('entity')->getValue();

    if (!isset($entity)) {
      return $values;
    }

    if ($entity instanceof ConfigEntityInterface) {
      $values['target_id'] = $entity->id();
    }
    else {
      // Add magical support to link to users that are generated automatically.
      // This is the case with admin 1 and the anonymous user. In this case
      // We'll add a special placeholder readable by the denormalizer.
      if ($entity->getEntityTypeId() == 'user' && in_array($entity->id(), [0, 1])) {
        $values['target_type'] = $entity->getEntityTypeId();
        $values['target_magic_id'] = $entity->id();
      }
      else {
        // Add the target entity UUID to the normalized output values.
        $values['target_type'] = $entity->getEntityTypeId();
        $values['target_uuid'] = $entity->uuid();
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {

    // Config entity references can be returned as is.
    if (isset($data['target_id'])) {
      return $data;
    }

    // When importing without references we'll just assume this field to be
    // empty in case of a content entity. This prevents the pollution of
    // preserved id's.
    if ($context['ignore_references']) {
      return [];
    }

    // If the item is connected to a magic id, use that to handle the reference.
    if (isset($values['target_magic_id'])) {
      return ['target_id' => $values['target_magic_id']];
    }

    // Otherwise decode the data based on the uuid.
    $targetEntity = $this->loadTargetEntity($data, $context);
    if (isset($targetEntity)) {
      return ['target_id' => $targetEntity->id()];
    }

    // Empty if the item couldn't be found.
    return [];
  }

  /**
   * Load the connected entity.
   *
   * @param array $data
   *   Source data.
   * @param array $context
   *   Context for the normalizer.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The associated entity if it could be found.
   */
  protected function loadTargetEntity(array $data, array $context) {
    if (isset($data['target_uuid'])) {
      // @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $field_item
      $field_item = $context['target_instance'];
      if (empty($data['target_uuid'])) {
        throw new InvalidArgumentException(sprintf('If provided "target_uuid" cannot be empty for field "%s".', $data['target_type'], $data['target_uuid'], $field_item->getName()));
      }
      $target_type = $field_item->getFieldDefinition()->getSetting('target_type');
      if (!empty($data['target_type']) && $target_type !== $data['target_type']) {
        throw new UnexpectedValueException(sprintf('The field "%s" property "target_type" must be set to "%s" or omitted.', $field_item->getFieldDefinition()->getName(), $target_type));
      }
      if ($entity = $this->entityRepository->loadEntityByUuid($target_type, $data['target_uuid'])) {
        // @TODO Improve logging.
        // echo sprintf('Linking "%s:%s"', $data['target_type'], $entity->id()) . "\n";
        return $entity;
      }
      else {
        // Unable to load entity by uuid.
        // @TODO Make this more strict.
        // @TODO Improve logging.
        echo sprintf('No "%s" entity found with UUID "%s" for field "%s".', $data['target_type'], $data['target_uuid'], $field_item->getName()) . "\n";
      }
    }
  }

}
