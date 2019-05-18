<?php

namespace Drupal\content_synchronizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Export entity entities.
 *
 * @ingroup content_synchronizer
 */
class ExportEntityListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Export entity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\content_synchronizer\Entity\ExportEntity */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.export_entity.canonical', [
          'export_entity' => $entity->id(),
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
        'title'  => t('Export'),
        'weight' => 1,
        'url'    => new Url(
          'entity.export_entity.canonical', [
            'export_entity' => $entity->id(),
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
