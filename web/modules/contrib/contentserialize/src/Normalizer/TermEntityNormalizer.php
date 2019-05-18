<?php

namespace Drupal\contentserialize\Normalizer;

/**
 * Defines a class for normalizing terms to ensure parent is stored.
 *
 * @see \Drupal\default_content\Normalizer\TermEntityNormalizer
 */
class TermEntityNormalizer extends UuidContentEntityNormalizer {

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
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    if ($parents = $this->getTermStorage()->loadParents($entity->id())) {
      $entity->parent->setValue(array_keys($parents));
    }
    return parent::normalize($entity, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $term = parent::denormalize($data, $class, $format, $context);
    if (is_null($term->parent->target_id)) {
      $term->parent->target_id = 0;
    }
    return $term;
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
      $this->termStorage = $this->entityManager->getStorage('taxonomy_term');
    }
    return $this->termStorage;
  }

}
