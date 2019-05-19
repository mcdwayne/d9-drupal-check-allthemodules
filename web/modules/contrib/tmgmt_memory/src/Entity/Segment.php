<?php

namespace Drupal\tmgmt_memory\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\tmgmt_memory\SegmentInterface;

/**
 * Entity class for the tmgmt_memory_segment entity.
 *
 * @ContentEntityType(
 *   id = "tmgmt_memory_segment",
 *   label = @Translation("Segment"),
 *   handlers = {
 *     "storage" = "Drupal\tmgmt_memory\SegmentStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   module = "tmgmt_memory",
 *   base_table = "tmgmt_memory_segment",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 *
 * @ingroup tmgmt_memory
 */
class Segment extends ContentEntityBase implements SegmentInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The language of the segment.'));

    $fields['stripped_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Stripped source'))
      ->setDescription(t('The text of this segment without HTML tags.'));

    $fields['count_usages'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Usages count'))
      ->setDescription(t('Number of usages of the segment.'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage() {
    return $this->get('language')->language;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    return $this->get('language')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStrippedData() {
    return $this->get('stripped_data')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function countUsages() {
    return $this->get('count_usages')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementCounterUsages() {
    $counter = $this->get('count_usages')->value;
    $counter++;
    $this->set('count_usages', $counter);
  }

}
