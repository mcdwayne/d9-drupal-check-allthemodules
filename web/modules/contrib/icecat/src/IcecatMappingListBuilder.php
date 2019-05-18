<?php

namespace Drupal\icecat;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Class IcecatMappingListBuilder.
 *
 * @package Drupal\icecat
 */
class IcecatMappingListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['entity'] = $this->t('Entity');
    $header['bundle'] = $this->t('Bundle');
    $header['source_field'] = $this->t('Source field');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['entity'] = $entity->getMappingEntityType();
    $row['bundle'] = $entity->getMappingEntityBundle();
    $row['source_field'] = $entity->getDataInputField();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\icecat\Entity\IcecatMappingInterface $entity */
    $operations = parent::getDefaultOperations($entity);
    $operations['icecat_mapping_links'] = [
      'title' => $this->t('View mapping links'),
      'url' => Url::fromRoute('entity.icecat_mapping_link.collection', [
        'icecat_mapping' => $entity->id(),
      ]),
    ];

    return $operations;
  }

}
