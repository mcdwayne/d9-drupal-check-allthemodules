<?php

namespace Drupal\formazing;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Formazing entity entities.
 *
 * @ingroup formazing
 */
class FormazingEntityListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Formazing entity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\formazing\Entity\FormazingEntity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.formazing_entity.edit_form',
      ['formazing_entity' => $entity->id()]
    );

    $row['operations']['data'] = [
      '#type' => 'operations',
      '#links' => [
        'edit' => [
          'url' => Url::fromRoute('entity.formazing_entity.edit_form', [
            'formazing_entity' => $entity->id(),
          ]),
          'title' => $this->t('Edit'),
          'weight' => 0,
        ],
        'delete' => [
          'url' => Url::fromRoute('entity.formazing_entity.delete_form', [
            'formazing_entity' => $entity->id(),
          ]),
          'title' => $this->t('Delete'),
          'weight' => 1,
        ],
        'submissions_export' => [
          'url' => Url::fromRoute('formazing.export_submissions', [
            'form' => $entity->id(),
          ]),
          'title' => $this->t('Export submissions (CSV)'),
          'weight' => 1
        ],
        'json_export' => [
          'url' => Url::fromRoute('formazing.exported_json', [
            'formazing_id' => $entity->id(),
          ]),
          'title' => $this->t('Json export'),
          'weight' => 2,
        ],
      ],
    ];

    return $row + parent::buildRow($entity);
  }

}
