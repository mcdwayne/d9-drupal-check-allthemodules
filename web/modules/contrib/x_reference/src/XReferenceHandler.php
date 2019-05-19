<?php


namespace Drupal\x_reference;


use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\x_reference\Entity\XReferencedEntity;
use Drupal\x_reference\Entity\XReference;
use Drupal\x_reference\Entity\XReferenceType;
use Drupal\x_reference\Exception\InvalidXReferenceException;
use Drupal\x_reference\Exception\XReferencedEntityNotSavedException;
use Drupal\x_reference\Exception\XReferenceTypeNotFoundException;

class XReferenceHandler implements XReferenceHandlerInterface {

  /** @var EntityStorageInterface */
  protected $XReferenceStorage;

  /** @var EntityStorageInterface */
  protected $XReferencedEntityStorage;

  /**
   * XReferenceHandler constructor.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->XReferenceStorage = $entityTypeManager->getStorage('x_reference');
    $this->XReferencedEntityStorage = $entityTypeManager->getStorage('x_referenced_entity');
  }

  /**
   * {@inheritdoc}
   */
  public function checkXReference($referenceType, XReferencedEntity $source, XReferencedEntity $target) {
    return (bool) $this->loadXReference($referenceType, $source, $target);
  }

  /**
   * {@inheritdoc}
   */
  public function loadXReference($referenceType, XReferencedEntity $source, XReferencedEntity $target) {
    if (!$this->checkXReferenceType($referenceType)) {
      throw new XReferenceTypeNotFoundException($referenceType);
    }

    /** @var XReference[] $references */
    $references = $this->XReferenceStorage->loadByProperties([
      'type' => $referenceType,
      'source_entity' => $source->id(),
      'target_entity' => $target->id(),
    ]);
    return $references
      ? reset($references)
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createOrLoadXReference($referenceType, XReferencedEntity $source, XReferencedEntity $target, $saveIfCreated = TRUE, $validate = TRUE) {
    if (!$this->checkXReferenceType($referenceType)) {
      throw new XReferenceTypeNotFoundException($referenceType);
    }

    /** @var XReferencedEntity $XReferencedEntity */
    foreach ([$source, $target] as $XReferencedEntity) {
      if ($XReferencedEntity->isNew()) {
        throw new XReferencedEntityNotSavedException();
      }
    }

    $reference = $this->loadXReference($referenceType, $source, $target);
    if (!$reference) {
      $reference = XReference::create([
        'type' => $referenceType,
        'source_entity' => $source,
        'target_entity' => $target,
      ]);
      if ($saveIfCreated) {
        if ($validate) {
          /** @var EntityConstraintViolationListInterface $violations */
          $violations = $reference->validate();
          if ($violations->count() > 0) {
            throw new InvalidXReferenceException($violations);
          }
        }
        $reference->save();
      }
    }

    return $reference;
  }

  /**
   * {@inheritdoc}
   */
  public function createReference($referenceType, XReferencedEntity $source, XReferencedEntity $target) {
    if (!$this->checkXReferenceType($referenceType)) {
      throw new XReferenceTypeNotFoundException($referenceType);
    }

    /** @var XReferencedEntity $XReferencedEntity */
    foreach ([$source, $target] as $XReferencedEntity) {
      if ($XReferencedEntity->isNew()) {
        throw new XReferencedEntityNotSavedException();
      }
    }

    return XReference::create([
      'type' => $referenceType,
      'source_entity' => $source,
      'target_entity' => $target,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function loadTargetsBySource($referenceType, XReferencedEntity $source) {
    if (!$this->checkXReferenceType($referenceType)) {
      throw new XReferenceTypeNotFoundException($referenceType);
    }

    if ($source->isNew()) {
      throw new XReferencedEntityNotSavedException();
    }

    /** @var XReference[] $references */
    $references = $this->XReferenceStorage->loadByProperties([
      'type' => $referenceType,
      'source_entity' => $source->id(),
    ]);
    $result = [];
    foreach ($references as $reference) {
      $targetEntity = $reference->getTargetEntity();
      if ($targetEntity) {
        $result[$targetEntity->id()] = $targetEntity;
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function loadSourcesByTarget($referenceType, XReferencedEntity $target) {
    if (!$this->checkXReferenceType($referenceType)) {
      throw new XReferenceTypeNotFoundException($referenceType);
    }

    if ($target->isNew()) {
      throw new XReferencedEntityNotSavedException();
    }

    /** @var XReference[] $references */
    $references = $this->XReferenceStorage->loadByProperties([
      'type' => $referenceType,
      'target_entity' => $target->id(),
    ]);
    $result = [];
    foreach ($references as $reference) {
      $sourceEntity = $reference->getSourceEntity();
      if ($sourceEntity) {
        $result[$sourceEntity->id()] = $sourceEntity;
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function checkXReferenceType($referenceType) {
    return (bool) XReferenceType::load($referenceType);
  }

  /**
   * {@inheritdoc}
   */
  public function createOrLoadXReferencedEntity($entity_source, $entity_type, $entity_id, $saveIfCreated = TRUE) {
    $XReferencedEntity = $this->XReferencedEntityStorage->loadByProperties([
      'entity_source' => $entity_source,
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
    ]);
    if ($XReferencedEntity) {
      $XReferencedEntity = reset($XReferencedEntity);
    }
    else {
      $XReferencedEntity = $this->createXReferencedEntity($entity_source, $entity_type, $entity_id);
      if ($saveIfCreated) {
        $XReferencedEntity->save();
      }
    }

    return $XReferencedEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function createXReferencedEntity($entity_source, $entity_type, $entity_id) {
    return XReferencedEntity::create([
      'entity_source' => $entity_source,
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
    ]);
  }

}
