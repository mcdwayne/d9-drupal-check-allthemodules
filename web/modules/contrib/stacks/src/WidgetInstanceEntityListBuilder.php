<?php

namespace Drupal\stacks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Widget Instance entity entities.
 *
 * @ingroup stacks
 */
class WidgetInstanceEntityListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = t('Entity ID');
    $header['title'] = t('Title');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\stacks\Entity\WidgetInstanceEntity */
    $row['id'] = $entity->id();
    $row['title'] = $this->l(
      $entity->label(),
      new Url(
        'entity.widget_instance_entity.edit_form', [
          'widget_instance_entity' => $entity->id(),
        ]
      )
    );

    return $row + parent::buildRow($entity);
  }

}
