<?php

namespace Drupal\widget_engine;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Widget entities.
 *
 * @ingroup widget_engine
 */
class WidgetListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Widget ID');
    $header['name'] = $this->t('Name');
    $header['bundle'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\widget_engine\Entity\Widget */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.widget.edit_form', array(
          'widget' => $entity->id(),
        )
      )
    );
    $row['bundle'] = $entity->type->entity->label();

    return $row + parent::buildRow($entity);
  }

}
