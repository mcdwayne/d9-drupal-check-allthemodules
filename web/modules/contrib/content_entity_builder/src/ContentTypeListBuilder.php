<?php

namespace Drupal\content_entity_builder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of blocktabs entities.
 *
 * @see \Drupal\blocktabs\Entity\BlockTabs
 */
class ContentTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
	$entity_type = $entity->id();
    $operations['list'] = [
      'title' => t('List'),
      'weight' => 10,
      //'url' => $entity->toUrl('collection'),
	  'url' => Url::fromRoute("entity.{$entity_type}.collection", [])
    ];	
    // Place the edit operation after the operations added by field_ui.module
    // which have the weights 15, 20, 25.
    if (isset($operations['edit'])) {
      //$operations['edit']['weight'] = 30;
      $operations['edit']['url'] = $entity->toUrl('edit-form');
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No content entity types available. <a href=":link">Add content entity type</a>.', [
      ':link' => Url::fromRoute('content_entity_builder.type_add')->toString(),
    ]);
    return $build;
  }

}
