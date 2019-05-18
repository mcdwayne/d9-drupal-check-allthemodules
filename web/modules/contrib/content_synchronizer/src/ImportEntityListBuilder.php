<?php

namespace Drupal\content_synchronizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Import entities.
 *
 * @ingroup content_synchronizer
 */
class ImportEntityListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Import ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\content_synchronizer\Entity\ImportEntity */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.import_entity.canonical', [
          'import_entity' => $entity->id(),
        ]
      )
    );
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $operations['view'] =
      [
        'title'  => t('Import'),
        'weight' => 1,
        'url'    => new Url(
          'entity.import_entity.canonical', [
            'import_entity' => $entity->id(),
          ]
        )
      ];

    usort($operations, function ($a, $b) {
      if ($a['weight'] > $b['weight']) {
        return 1;
      }
      return -1;
    });
    return $operations;
  }

}
