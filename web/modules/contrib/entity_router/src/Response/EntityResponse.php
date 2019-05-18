<?php

namespace Drupal\entity_router\Response;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * The entity response for further processing.
 *
 * @see \Drupal\entity_router\EventSubscriber::onResponse()
 */
class EntityResponse extends Response {

  /**
   * The format to convert the entity to.
   *
   * @var string
   */
  protected $format;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function __construct(?EntityInterface $entity, string $format, int $status) {
    parent::__construct('', $status, []);

    $this->entity = $entity;
    $this->format = $format;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): ? EntityInterface {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormat(): string {
    return $this->format;
  }

}
