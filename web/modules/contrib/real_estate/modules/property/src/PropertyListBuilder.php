<?php

namespace Drupal\real_estate_property;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Property entities.
 *
 * @ingroup real_estate_property
 */
class PropertyListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Property ID');
    $header['title'] = $this->t('Title');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\real_estate_property\Entity\Property */
    $row['id'] = $entity->id();
    $row['title'] = $this->l(
      $entity->label(),
      new Url(
        'entity.real_estate_property.canonical', [
          'real_estate_property' => $entity->id(),
        ]
      )
    );
    return $row + parent::buildRow($entity);
  }

}
