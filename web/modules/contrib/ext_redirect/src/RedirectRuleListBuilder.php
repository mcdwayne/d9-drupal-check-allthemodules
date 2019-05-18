<?php

namespace Drupal\ext_redirect;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Redirect Rule entities.
 *
 * @ingroup ext_redirect
 */
class RedirectRuleListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['source_site'] = $this->t('Source Site');
    $header['source_path'] = $this->t('Source Path');
    $header['destination'] = $this->t('Destination');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\ext_redirect\Entity\RedirectRule */
    $row['id'] = $entity->id();
    $row['source_site'] = $entity->getSourceSite();
    $row['source_path'] = $entity->getSourcePath();
    $url = $entity->getDestinationUrl();
    $row['destination'] = new Link($url->toString(), $url);


    return $row + parent::buildRow($entity);
  }

}
