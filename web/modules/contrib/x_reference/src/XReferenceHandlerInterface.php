<?php


namespace Drupal\x_reference;


use Drupal\Core\Entity\EntityStorageException;
use Drupal\x_reference\Entity\XReferencedEntity;
use Drupal\x_reference\Entity\XReference;
use Drupal\x_reference\Exception\InvalidXReferenceException;
use Drupal\x_reference\Exception\XReferencedEntityNotSavedException;
use Drupal\x_reference\Exception\XReferenceTypeNotFoundException;

interface XReferenceHandlerInterface {

  /**
   * @param string $referenceType
   * @param XReferencedEntity $source
   * @param XReferencedEntity $target
   *
   * @return bool
   *
   * @throws XReferenceTypeNotFoundException
   */
  public function checkXReference($referenceType, XReferencedEntity $source, XReferencedEntity $target);

  /**
   * @param string $referenceType
   * @param XReferencedEntity $source
   * @param XReferencedEntity $target
   *
   * @return XReference|null
   *
   * @throws XReferenceTypeNotFoundException
   */
  public function loadXReference($referenceType, XReferencedEntity $source, XReferencedEntity $target);

  /**
   * @param string $referenceType
   * @param XReferencedEntity $source
   * @param XReferencedEntity $target
   * @param bool $saveIfCreated
   * @param bool $validate
   *
   * @return XReference
   *
   * @throws XReferencedEntityNotSavedException
   * @throws XReferenceTypeNotFoundException
   * @throws InvalidXReferenceException
   */
  public function createOrLoadXReference($referenceType, XReferencedEntity $source, XReferencedEntity $target, $saveIfCreated = TRUE, $validate = TRUE);

  /**
   * @param string $referenceType
   * @param XReferencedEntity $source
   * @param XReferencedEntity $target
   *
   * @return XReference
   *
   * @throws XReferencedEntityNotSavedException
   * @throws XReferenceTypeNotFoundException
   */
  public function createReference($referenceType, XReferencedEntity $source, XReferencedEntity $target);

  /**
   * @param string $referenceType
   * @param XReferencedEntity $source
   *
   * @return XReferencedEntity[]
   *
   * @throws XReferencedEntityNotSavedException
   * @throws XReferenceTypeNotFoundException
   */
  public function loadTargetsBySource($referenceType, XReferencedEntity $source);

  /**
   * @param string $referenceType
   * @param XReferencedEntity $target
   *
   * @return XReferencedEntity[]
   *
   * @throws XReferencedEntityNotSavedException
   * @throws XReferenceTypeNotFoundException
   */
  public function loadSourcesByTarget($referenceType, XReferencedEntity $target);

  /**
   * @param string $referenceType
   *
   * @return bool
   */
  public function checkXReferenceType($referenceType);

  /**
   * @param string $entity_source
   * @param string $entity_type
   * @param string $entity_id
   * @param bool $saveIfCreated
   *
   * @return XReferencedEntity
   *
   * @throws EntityStorageException
   */
  public function createOrLoadXReferencedEntity($entity_source, $entity_type, $entity_id, $saveIfCreated = TRUE);

  /**
   * @param string $entity_source
   * @param string $entity_type
   * @param string $entity_id
   *
   * @return XReferencedEntity
   */
  public function createXReferencedEntity($entity_source, $entity_type, $entity_id);

}
