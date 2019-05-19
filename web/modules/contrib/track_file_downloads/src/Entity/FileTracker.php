<?php

namespace Drupal\track_file_downloads\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the FileTracker entity.
 *
 * @ContentEntityType(
 *   id = "file_tracker",
 *   label = @Translation("File tracker"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\track_file_downloads\FileTrackerListBuilder",
 *     "views_data" = "Drupal\track_file_downloads\Entity\FileTrackerViewsData",
 *     "access" = "Drupal\track_file_downloads\FileTrackerAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\track_file_downloads\FileTrackerHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "file_tracker",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/file_tracker/{file_tracker}",
 *     "collection" = "/admin/content/file_tracker",
 *   },
 * )
 */
class FileTracker extends ContentEntityBase implements FileTrackerInterface {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    if (!$this->file->isEmpty()) {
      return $this->get('file')->entity;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getFile()->label();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['file'] = BaseFieldDefinition::create('file')
      ->setLabel(t('File'))
      ->setDefaultValue('')
      ->setRequired(TRUE)
      ->setCardinality(1)
      ->setSetting('uri_scheme', 'private')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'file_default',
        'weight' => -6,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['download_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Download Count'))
      ->setDescription(t('Keep track of the number of file downloads'))
      ->setDefaultValue(0);

    $fields['last_downloaded_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last Downloaded Date'))
      ->setDescription(t('Keep track of the last date and time the file was downloaded.'))
      ->setDefaultValue(NULL);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementDownloadCount() {
    $this->set('download_count', (int) $this->getDownloadCount() + 1);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDownloadCount(): int {
    return (int) $this->get('download_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLastDownloadedDate() {
    $this->set('last_downloaded_date', \Drupal::time()->getRequestTime());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastDownloadedDate(): int {
    return (int) $this->get('last_downloaded_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToSkipFromTranslationChangesCheck() {
    $fields = parent::getFieldsToSkipFromTranslationChangesCheck();
    $fields[] = 'download_count';
    $fields[] = 'last_downloaded_date';
    return $fields;
  }

}
