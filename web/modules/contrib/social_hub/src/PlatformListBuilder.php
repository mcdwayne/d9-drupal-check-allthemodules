<?php

namespace Drupal\social_hub;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of platforms.
 */
class PlatformListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['plugins'] = $this->t('Plugins');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    /** @var \Drupal\social_hub\PlatformInterface $entity */
    $collection = $entity->getPluginCollection()->getIterator();
    $plugins = [];
    /** @var \ArrayIterator $collection */
    while ($collection->valid()) {
      /** @var \Drupal\social_hub\PlatformIntegrationPluginInterface $plugin */
      $plugin = $collection->current();
      $plugins[$plugin->getPluginId()] = $plugin->getLabel();
      $collection->next();
    }
    asort($plugins);
    $row['plugins'] = implode(', ', $plugins);
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');

    return $row + parent::buildRow($entity);
  }

}
