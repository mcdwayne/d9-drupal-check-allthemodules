<?php

/**
 * @file
 * Contains Drupal\quick_pages\Controller\QuickPageListBuilder.
 */

namespace Drupal\quick_pages\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a listing of quick pages.
 */
class QuickPageListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['status'] = $this->t('Status');
    $header['path'] = $this->t('path');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\quick_pages\Entity\QuickPage $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');

    $path = $entity->get('path');
    $row['path'] = preg_match('#\{.+\}#', $path) ?
      $path : Link::fromTextAndUrl($path, Url::fromUserInput($entity->get('path')));

    return $row + parent::buildRow($entity);
  }

}
