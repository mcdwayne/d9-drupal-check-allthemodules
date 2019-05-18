<?php

namespace Drupal\cloudwords;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Cloudwords project entities.
 *
 * @ingroup cloudwords
 */
class CloudwordsProjectListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Cloudwords project ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\cloudwords\Entity\CloudwordsProject */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      new Url(
        'entity.cloudwords_project.edit_form', [
          'cloudwords_project' => $entity->id(),
        ]
      )
    );
    return $row + parent::buildRow($entity);
  }

}
