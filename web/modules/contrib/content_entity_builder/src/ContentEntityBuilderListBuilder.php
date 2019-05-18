<?php

namespace Drupal\content_entity_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of content entities.
 *
 * @see \Drupal\content_entity_builder\Entity\Content
 */
class ContentEntityBuilderListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();  
    $label = !empty($entity->label()) ? $entity->label() : $entity->id();
	$row['label'] = new Link($label, Url::fromRoute("entity.{$entity_type}.canonical", [$entity_type => $entity->id()]));
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    return $operations;
  }

}
