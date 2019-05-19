<?php

namespace Drupal\sapi_data;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Statistics API Data entry entities.
 *
 * @ingroup sapi_data
 */
class SAPIDataListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['type'] = $this->t('Data entry type');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\sapi_data\Entity\SAPIData */
    $row['id'] = $entity->id();
    $row['type'] = $entity->getType();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.sapi_data.canonical', array(
          'sapi_data' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
