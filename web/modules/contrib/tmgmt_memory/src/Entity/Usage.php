<?php

namespace Drupal\tmgmt_memory\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\tmgmt_memory\UsageInterface;

/**
 * Entity class for the tmgmt_memory_usage entity.
 *
 * @ContentEntityType(
 *   id = "tmgmt_memory_usage",
 *   label = @Translation("Segment Usage"),
 *   handlers = {
 *     "access" = "Drupal\tmgmt_memory\Entity\Controller\UsageAccessControlHandler",
 *     "list_builder" = "Drupal\tmgmt_memory\Entity\ListBuilder\UsageListBuilder",
 *     "storage" = "Drupal\tmgmt_memory\UsageStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   module = "tmgmt_memory",
 *   base_table = "tmgmt_memory_usage",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/tmgmt/memory/usages/{tmgmt_memory_usage}",
 *   }
 * )
 *
 * @ingroup tmgmt_memory
 */
class Usage extends ContentEntityBase implements UsageInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['job_item_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Job Item'))
      ->setDescription(t('The Job Item of the usage.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'tmgmt_job_item');

    $fields['data_item_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Data Item'))
      ->setDescription(t('The data item of the usage.'));

    $fields['segment_delta'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Segment delta'))
      ->setDescription(t('The order of the segment inside the data item.'))
      ->setSetting('unsigned', TRUE);

    $fields['segment_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Segment'))
      ->setDescription(t('TMGMT Memory Segment ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'tmgmt_memory_segment');

    $fields['data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Data'))
      ->setDescription(t('The text of this segment with HTML tags.'));

    $fields['context_data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Context data'))
      ->setDescription(t('Context data about this usage.'))
      ->setDefaultValue([]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getJobItemId() {
    return $this->get('job_item_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getJobItem() {
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_job_item');
    return $storage->load($this->getJobItemId());
  }

  /**
   * {@inheritdoc}
   */
  public function getDataItemKey() {
    return $this->get('data_item_key')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSegmentDelta() {
    return $this->get('segment_delta')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSegmentId() {
    return $this->get('segment_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getSegment() {
    /** @var \Drupal\tmgmt_memory\SegmentStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_segment');
    return $storage->load($this->getSegmentId());
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->get('data')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getContextData() {
    return $this->get('context_data')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage() {
    $segment = $this->getSegment();
    return $segment->getLanguage();
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    $segment = $this->getSegment();
    return $segment->getLangcode();
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    /** @var \Drupal\tmgmt_memory\SegmentInterface $segment */
    $segment = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_segment')->load($this->get('segment_id')->target_id);
    $segment->incrementCounterUsages();
    $segment->save();
  }

}
