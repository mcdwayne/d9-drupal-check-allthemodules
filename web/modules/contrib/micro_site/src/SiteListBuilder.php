<?php

namespace Drupal\micro_site;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Site entities.
 *
 * @ingroup micro_site
 */
class SiteListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Site ID');
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Type');
    $header['registered'] = $this->t('Registered');
    $header['status'] = $this->t('Published');
    $header['url'] = $this->t('Url');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\micro_site\Entity\Site */
    $row['id'] = $entity->id();
    $row['name'] = $entity->label();
    $row['type'] = $entity->type->entity ? $entity->type->entity->label() : '';
    $row['registered'] = $entity->isRegistered() ? $this->t('registered') : $this->t('not registered');
    $row['status'] = $entity->isPublished() ? $this->t('published') : $this->t('not published');
    $row['url'] = Link::createFromRoute(
      $entity->label(),
      'entity.site.canonical',
      ['site' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
