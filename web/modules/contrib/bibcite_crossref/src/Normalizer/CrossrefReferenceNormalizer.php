<?php

namespace Drupal\bibcite_crossref\Normalizer;

use Drupal\bibcite_entity\Normalizer\ReferenceNormalizerBase;

/**
 * Normalizes/denormalizes reference entity to Crossref format.
 */
class CrossrefReferenceNormalizer extends ReferenceNormalizerBase {

  /**
   * {@inheritdoc}
   */
  public function normalize($reference, $format = NULL, array $context = []) {
    throw new \Exception("Can't normalize to Crossref format.");
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $contributor_key = $this->getContributorKey();
    if (!empty($data[$contributor_key])) {
      $contributors = (array) $data[$contributor_key];
      $roles = array_column($contributors, 'role');
      $data[$contributor_key] = array_column($contributors, 'value');
    }

    $entity = parent::denormalize($data, $class, $format, $context);

    if (!empty($contributors)) {
      $author_field = $entity->get('author');
      for ($i = 0; $i < $author_field->count(); $i++) {
        $author = $author_field->get($i);
        $role = $author->getProperties()['role'];
        $role->setValue($roles[$i]);
      }
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return FALSE;
  }

}
