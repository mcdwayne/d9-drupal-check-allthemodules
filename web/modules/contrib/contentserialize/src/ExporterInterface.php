<?php

namespace Drupal\contentserialize;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a content exporter.
 */
interface ExporterInterface {

  /**
   * Export a single entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to export.
   * @param string $format
   *   The serialization format.
   * @param array $context
   *   (optional) A context array for the serializer.
   *
   * @return \Drupal\contentserialize\SerializedEntity
   *   The serialized entity object
   *
   * @throws \Symfony\Component\Serializer\Exception\InvalidArgumentException
   *   If the entity isn't a content entity.
   */
  public function export(ContentEntityInterface $entity, $format, array $context = []);

  /**
   * Serializes entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[]|\Traversable $entities
   *   The content entities to export.
   * @param string $format
   *   The serialization format.
   * @param array $context
   *   (optional) A context array for the serializer.
   *
   * @return \Generator|\Drupal\contentserialize\SerializedEntity[]
   *   The serialized entity object
   *
   * @throws \Symfony\Component\Serializer\Exception\InvalidArgumentException
   *   If any of the entities aren't content entities.
   */
  public function exportMultiple($entities, $format, array $context = []);

}