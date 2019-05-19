<?php

namespace Drupal\pusher_integration\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Builds the list of associations for the channel path map form.
 */
class ChannelPathMapListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    module_load_include('inc', 'pusher_integration');
    $header['mapId'] = $this->t('Channel-Path-Map ID');
    $header['channelName'] = $this->t('Pusher Channel Name');
    $header['pathPattern'] = $this->t('Path Pattern');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['mapId'] = $entity->id();

    $row['channelName'] = $entity->getChannelName();
    $row['pathPattern'] = $entity->getPathPattern();

    return $row + parent::buildRow($entity);
  }

}
