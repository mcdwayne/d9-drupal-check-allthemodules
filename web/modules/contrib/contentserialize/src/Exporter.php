<?php

namespace Drupal\contentserialize;

use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Serializes entities and metadata with Drupal's serializer service.
 *
 * @todo Export dependencies so the import can be done in a sensible order.
 * @todo Return Result object.
 */
class Exporter implements ExporterInterface {

  /**
   * The serialializer service.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * Creates a new Exporter.
   *
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer service.
   */
  public function __construct(SerializerInterface $serializer) {
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public function export(ContentEntityInterface $entity, $format, array $context = []) {
    if (!$entity instanceof ContentEntityInterface) {
      throw new InvalidArgumentException("Trying to export entity that isn't a content entity");
    }
    $serialized_data = $this->serializer->serialize($entity, $format, $context);
    return new SerializedEntity($serialized_data, $format, $entity->uuid(), $entity->getEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function exportMultiple($entities, $format, array $context = []) {
    foreach ($entities as $entity) {
      yield $this->export($entity, $format, $context);
    }
  }

}