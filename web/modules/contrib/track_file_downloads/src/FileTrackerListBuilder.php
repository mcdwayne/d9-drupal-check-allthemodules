<?php

namespace Drupal\track_file_downloads;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of File Tracker entities.
 */
class FileTrackerListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'file' => $this->t('File'),
      'downloads' => $this->t('Downloads'),
      'last_download_date' => $this->t('Last download date'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\track_file_downloads\Entity\FileTracker */
    $row['file'] = $entity->getFile()->label();
    $row['downloads'] = $entity->getDownloadCount();
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    $row['last_download_date'] = $date_formatter->format($entity->getLastDownloadedDate());
    return $row;
  }

}
