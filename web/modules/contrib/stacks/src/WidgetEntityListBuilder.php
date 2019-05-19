<?php

namespace Drupal\stacks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Widget Entity entities.
 *
 * @ingroup stacks
 */
class WidgetEntityListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = t('Entity ID');
    $header['title'] = t('Widget Title');
    $header['bundle'] = t('Bundle');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\stacks\Entity\WidgetEntity */
    $row['id'] = $entity->id();
    $row['title'] = $this->l(
      $entity->label(),
      new Url(
        'entity.widget_entity.edit_form', [
          'widget_entity' => $entity->id(),
        ]
      )
    );

    $row['bundle'] = $entity->getType();
    return $row + parent::buildRow($entity);
  }

}
