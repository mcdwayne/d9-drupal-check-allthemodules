<?php

namespace Drupal\cloud\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Cloud config entities.
 *
 * @ingroup cloud
 */
class CloudConfigListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Cloud config ID');
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Type');
    $header['cloud_context'] = $this->t('Cloud Context');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\cloud\Entity\CloudConfig */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.cloud_config.edit_form',
      ['cloud_config' => $entity->id()]
    );
    $row['type'] = $entity->bundle();
    $row['cloud_context'] = $entity->getCloudContext();
    return $row + parent::buildRow($entity);
  }

}
