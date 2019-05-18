<?php

namespace Drupal\acquia_contenthub\Event;

use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * The event which adds custom CDF attributes to the CDF objects.
 */
class CdfAttributesEvent extends Event {

  /**
   * The CDF Object for which to create attributes.
   *
   * @var \Acquia\ContentHubClient\CDF\CDFObject
   */
  protected $cdf;

  /**
   * The entity which corresponds to the CDF object.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * CdfAttributesEvent constructor.
   *
   * @param \Acquia\ContentHubClient\CDF\CDFObject $cdf
   *   The CDF object.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function __construct(CDFObject $cdf, EntityInterface $entity) {
    $this->cdf = $cdf;
    $this->entity = $entity;
  }

  /**
   * Get the CDF being created.
   *
   * @return \Acquia\ContentHubClient\CDF\CDFObject
   *   The CDF object.
   */
  public function getCdf() {
    return $this->cdf;
  }

  /**
   * Get the entity being processed.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity being processed.
   */
  public function getEntity() {
    return $this->entity;
  }

}
