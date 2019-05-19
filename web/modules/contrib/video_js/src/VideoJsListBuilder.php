<?php

namespace Drupal\video_js;

use Drupal\video_js\Entity\VideoJsInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\file\Entity\File;

/**
 * Provides a listing of BigVideo Source entities.
 */
class VideoJsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Source');
    $header['type'] = $this->t('Type');
    $header['format'] = $this->t('Format');
    $header['source'] = $this->t('Source');
    $header['status'] = $this->t('Status');
    $header['paths'] = $this->t('Paths');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\video_js\Entity\VideoJsInterface $entity */
    $row['label'] = $entity->label();
    $type = ($entity->getType() == VideoJsInterface::TYPE_FILE) ? $this->t('File') : $this->t('Link');
    $row['type'] = $type;
    switch ($type) {

      case 'File':
        $fid = $entity->get('file')->getValue()[0]['target_id'];
        $file = File::load($fid);
        $source = $file->getFilename();
        break;

      default:
        $source = $entity->get('link')->getValue()[0]['value'];

    }
    $row['format'] = $entity->get('format')->getValue()[0]['value'];
    $row['source'] = $source;
    $status = $entity->get('status')->getValue();
    $row['status'] = $status ? $this->t('Enabled') : $this->t('Disabled');
    $row['paths'] = $entity->get('paths')->getValue()[0]['value'];
    return $row + parent::buildRow($entity);
  }

}
