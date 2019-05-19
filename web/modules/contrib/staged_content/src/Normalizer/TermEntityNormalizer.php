<?php

namespace Drupal\staged_content\Normalizer;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Defines a class for normalizing terms to ensure parent is stored.
 */
class TermEntityNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\taxonomy\TermInterface';

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   *   The entity repository.
   */
  protected $entityRepository;

  /**
   * TermEntityNormalizer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module Handler.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   Entity Repository interface.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $moduleHandler, EntityRepositoryInterface $entityRepository) {
    parent::__construct($entityTypeManager, $moduleHandler);
    $this->entityRepository = $entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {

    $data = parent::normalize($entity, $format, $context);

    if ($parents = $this->getTermStorage()->loadParents($entity->id())) {
      foreach ($parents as $parent) {
        $data['parents'][] = $parent->uuid();
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $entity = parent::denormalize($data, $class, $format, $context);

    // For the first pass assume the correct parents have not been set.
    if ($context['ignore_references']) {
      $entity->parent->setValue([0]);
    }

    // For the second pass, attach the correct parents.
    else {
      $parentId = 0;
      if (isset($data['parents']) && is_array($data['parents'])) {
        foreach ($data['parents'] as $uuid) {
          $parent = $this->entityRepository->loadEntityByUuid('taxonomy_term', $uuid);
          if (isset($parent)) {
            $parentId = $parent->id();
          }
        }
      }
      $entity->parent->setValue([$parentId]);
    }

    return $entity;
  }

  /**
   * Returns taxonomy term storage.
   *
   * Prevents circular reference when used with multiversion.
   *
   * @return \Drupal\taxonomy\TermStorageInterface
   *   The taxonomy term storage.
   */
  protected function getTermStorage() {
    if (!$this->termStorage) {
      $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    }
    return $this->termStorage;
  }

}
