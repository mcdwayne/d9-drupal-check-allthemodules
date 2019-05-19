<?php

namespace Drupal\x_reference\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\x_reference\Entity\XReferenceType;
use Drupal\Core\Link;
use Drupal\Core\Url;


/**
 * Provides a listing of x_reference_type type entities.
 */
class XReferenceTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['machine_name'] = $this->t('Machine Name');
    $header['source_entity_source'] = $this->t('Source entity source');
    $header['source_entity_type'] = $this->t('Source entity type');
    $header['target_entity_source'] = $this->t('Target entity source');
    $header['target_entity_type'] = $this->t('Target entity type');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var XReferenceType $entity */
    $row['label'] = $entity->label();
    $row['machine_name'] = $entity->id();
    $row['source_entity_source'] = $entity->source_entity_source;
    $row['source_entity_type'] = $entity->source_entity_type;
    $row['target_entity_source'] = $entity->target_entity_source;
    $row['target_entity_type'] = $entity->target_entity_type;

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#prefix'] = Link::fromTextAndUrl(
      'Add X-reference type',
      Url::fromRoute('entity.x_reference_type.add_form')
    )->toString();

    return $build;
  }

}
