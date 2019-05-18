<?php

namespace Drupal\cshs;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class TaxonomyStorages.
 */
trait TaxonomyStorages {

  /**
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;
  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Get storage object for terms.
   *
   * @return \Drupal\taxonomy\TermStorage
   *   Taxonomy term storage.
   */
  protected function getTermStorage() {
    return $this->getStorage('taxonomy_term');
  }

  /**
   * Get storage object for vocabularies.
   *
   * @return \Drupal\taxonomy\VocabularyStorage
   *   Taxonomy vocabulary storage.
   */
  protected function getVocabularyStorage() {
    return $this->getStorage('taxonomy_vocabulary');
  }

  /**
   * Gets the entity translation to be used in the given context.
   *
   * @param EntityInterface $entity
   *   The entity whose translation will be returned.
   *
   * @return EntityInterface
   *   An entity object for the translated data.
   */
  protected function getTranslationFromContext(EntityInterface $entity) {
    if (NULL === $this->entityRepository) {
      $this->entityRepository = \Drupal::service('entity.repository');
    }

    return $this->entityRepository->getTranslationFromContext($entity);
  }

  /**
   * Get storage object for an entity.
   *
   * @param string $entity_type
   *   Name of an entity type.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   Storage object.
   */
  private function getStorage($entity_type) {
    if (NULL === $this->entityTypeManager) {
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }

    return $this->entityTypeManager->getStorage($entity_type);
  }

}
