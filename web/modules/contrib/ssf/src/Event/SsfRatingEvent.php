<?php

namespace Drupal\ssf\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * The SSF rating event class.
 */
class SsfRatingEvent extends Event {

  /**
   * Name of the event fired when rating/classifying content.
   *
   * @var string
   */
  const SSF_RATING = 'ssf.rating.event';

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * The type of content.
   *
   * @var string
   */
  protected $type;

  /**
   * The rating.
   *
   * @var float
   */
  protected $rating;

  /**
   * SsfRatingEvent constructor.
   *
   * @param ContentEntityInterface $entity
   *   The content entity.
   * @param string $type
   *   The content entity type.
   * @param float $rating
   *   The rating.
   */
  public function __construct(ContentEntityInterface $entity, $type, $rating) {
    $this->entity = $entity;
    $this->type = $type;
    $this->rating = $rating;
  }

  /**
   * @return \Drupal\Core\Entity\ContentEntity
   *   The $entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * @return string
   *   The $type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * @return float
   *   The $rating.
   */
  public function getRating() {
    return $this->rating;
  }

}
