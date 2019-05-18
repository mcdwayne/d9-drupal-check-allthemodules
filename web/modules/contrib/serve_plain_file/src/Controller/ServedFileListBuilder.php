<?php

namespace Drupal\serve_plain_file\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Served Files.
 */
class ServedFileListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['path'] = $this->t('Path');
    $header['content'] = $this->t('Content');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\serve_plain_file\Entity\ServedFileInterface $entity */
    $row['label'] = $entity->label();
    $row['path'] = $entity->getLinkToFile();
    $row['content'] = $entity->getContentHead();

    return $row + parent::buildRow($entity);
  }

}
