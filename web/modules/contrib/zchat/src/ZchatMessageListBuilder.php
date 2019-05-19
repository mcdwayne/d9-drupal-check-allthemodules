<?php

namespace Drupal\zchat;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Zchat Message entities.
 *
 * @ingroup zchat
 */
class ZchatMessageListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Zchat Message ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\zchat\Entity\ZchatMessage */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.zchatmessage.edit_form',
      ['zchatmessage' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
