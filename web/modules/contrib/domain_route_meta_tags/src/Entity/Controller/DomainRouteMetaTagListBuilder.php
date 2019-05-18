<?php

namespace Drupal\domain_route_meta_tags\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for domain_route_meta_tags entity.
 *
 * @ingroup domain_route_meta_tags
 */
class DomainRouteMetaTagListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('Meta Tags List.'),
    ];
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the Meta list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Meta ID');
    $header['route_link'] = $this->t('Route');
    $header['domain'] = $this->t('Domain');
    $header['title'] = $this->t('Title');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $domain = \Drupal::service('domain.loader')->load($entity->domain->value)->getHostname();
    $row['id'] = $entity->id();
    $row['route_link'] = $entity->route_link->value;
    $row['domain'] = $domain;
    $row['title'] = $entity->title->value;
    return $row + parent::buildRow($entity);
  }

}
