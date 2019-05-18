<?php

namespace Drupal\bibcite_entity\Normalizer;

use Drupal\serialization\Normalizer\EntityNormalizer;

/**
 * Base normalizer class for bibcite formats.
 */
class ContributorNormalizer extends EntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = ['Drupal\bibcite_entity\Entity\ContributorInterface'];

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $entity = parent::denormalize($data, $class, $format, $context);
    // @todo Use $this->entityTypeManager only, once Drupal 8.8.0 is released.
    $entity_manager = isset($this->entityTypeManager) ? $this->entityTypeManager : $this->entityManager;

    if (!empty($context['contributor_deduplication'])) {
      $storage = $entity_manager->getStorage('bibcite_contributor');
      $query = $storage->getQuery()->range(0, 1);
      // @todo Define this list somewhere publicly accessible for easy use.
      $name_parts = [
        'leading_title',
        'prefix',
        'first_name',
        'middle_name',
        'last_name',
        'nick',
        'suffix',
      ];
      foreach ($name_parts as $name_part) {
        $value = $entity->{$name_part}->value;
        if (!$value) {
          $query->notExists($name_part);
        }
        else {
          $query->condition($name_part, $value);
        }
      }

      $ids = $query->execute();
      if ($ids && ($result = $storage->loadMultiple($ids))) {
        return reset($result);
      }
    }

    return $entity;
  }

}
