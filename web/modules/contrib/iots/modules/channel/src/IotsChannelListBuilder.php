<?php

namespace Drupal\iots_channel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Iots Channel entities.
 *
 * @ingroup iots_channel
 */
class IotsChannelListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Iots Channel ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\iots_channel\Entity\IotsChannel */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.iots_channel.canonical',
      ['iots_channel' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
