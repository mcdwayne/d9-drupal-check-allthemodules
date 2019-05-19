<?php

namespace Drupal\wizenoze;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a listing of Search page entities.
 */
class WizenozePageListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Title');
    $header['path'] = $this->t('Path');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\wizenoze\WizenozePageInterface */
    $row['label'] = $entity->label();
    $path = $entity->getPath();
    if (!empty($path)) {
      $row['path'] = Link::fromTextAndUrl($entity->getPath(), Url::fromRoute('wizenoze_page.' . \Drupal::languageManager()->getDefaultLanguage()->getId() . '.' . $entity->id()));
    }
    else {
      $row['path'] = '';
    }
    return $row + parent::buildRow($entity);
  }

}
