<?php

namespace Drupal\eloqua_app_cloud;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Eloqua AppCloud Service entities.
 *
 * @ingroup eloqua_app_cloud
 */
class EloquaAppCloudServiceListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Type');
    $header['id'] = $this->t('Eloqua AppCloud Service ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\eloqua_app_cloud\Entity\EloquaAppCloudService */
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.eloqua_app_cloud_service.edit_form', array(
          'eloqua_app_cloud_service' => $entity->id(),
        )
      )
    );
    $row['type'] = $entity->bundle();
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

}
