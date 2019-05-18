<?php

namespace Drupal\drd\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Project entities.
 *
 * @ingroup drd
 */
class Project extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Project ID');
    $header['label'] = $this->t('Label');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\drd\Entity\ProjectInterface $entity */
    $row['id'] = $entity->id();
    $row['label'] = Link::fromTextAndUrl(
      $entity->label(),
      new Url(
        'entity.drd_project.edit_form', [
          'drd_project' => $entity->id(),
        ]
      )
    );
    $row['name'] = $entity->getName();
    return $row + parent::buildRow($entity);
  }

}
