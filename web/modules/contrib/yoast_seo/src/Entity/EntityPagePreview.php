<?php

namespace Drupal\yoast_seo\Entity;

use Drupal\Core\Entity\EntityInterface;

/**
 * A class to encapsulate entity analysis results.
 */
class EntityPagePreview implements EntityPreviewInterface {

  protected $entity;
  protected $language;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
    $this->language = $entity->language();
  }

  /**
   * {@inheritdoc}
   */
  public function language() {
    return $this->language;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }

}
