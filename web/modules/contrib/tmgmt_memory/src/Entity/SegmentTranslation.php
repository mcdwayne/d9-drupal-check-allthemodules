<?php

namespace Drupal\tmgmt_memory\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\tmgmt_memory\SegmentTranslationInterface;

/**
 * Entity class for the tmgmt_memory_segment_translation entity.
 *
 * @ContentEntityType(
 *   id = "tmgmt_memory_segment_translation",
 *   label = @Translation("Segment Translation"),
 *   handlers = {
 *     "access" = "Drupal\tmgmt_memory\Entity\Controller\SegmentTranslationAccessControlHandler",
 *     "form" = {
 *       "edit" = "Drupal\tmgmt_memory\Form\SegmentTranslationForm",
 *       "delete" = "Drupal\tmgmt_memory\Form\SegmentTranslationDeleteForm",
 *       "change-state" = "Drupal\tmgmt_memory\Form\ChangeConfirmationForm",
 *     },
 *     "list_builder" = "Drupal\tmgmt_memory\Entity\ListBuilder\SegmentTranslationListBuilder",
 *     "storage" = "Drupal\tmgmt_memory\SegmentTranslationStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   module = "tmgmt_memory",
 *   base_table = "tmgmt_memory_segment_translation",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/tmgmt/memory/{tmgmt_memory_segment_translation}",
 *     "delete-form" = "/admin/tmgmt/memory/{tmgmt_memory_segment_translation}/delete",
 *     "change-state" = "/admin/tmgmt/memory/{tmgmt_memory_segment_translation}/change-state",
 *   }
 * )
 *
 * @ingroup tmgmt_memory
 */
class SegmentTranslation extends ContentEntityBase implements SegmentTranslationInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['state'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('State'))
      ->setDescription(t('State of the SegmentTranslation.'));

    $fields['source'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Source segment'))
      ->setDescription(t('The source segment.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'tmgmt_memory_segment');

    $fields['target'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Target segment'))
      ->setDescription(t('The target segment.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'tmgmt_memory_segment');

    $fields['quality'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Quality'))
      ->setDescription(t('Quality of the translation.'))
      ->setSetting('min', 0)
      ->setSetting('max', 10);

    $fields['target_language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Target language'))
      ->setDescription(t('The target language of the segment translation.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceId() {
    return $this->get('source')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    /** @var \Drupal\tmgmt_memory\SegmentStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_segment');
    return $storage->load($this->getSourceId());
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetId() {
    return $this->get('target')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTarget() {
    /** @var \Drupal\tmgmt_memory\SegmentStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_segment');
    return $storage->load($this->getTargetId());
  }

  /**
   * {@inheritdoc}
   */
  public function getQuality() {
    return $this->get('quality')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setState($state) {
    $this->set('state', $state);
    /** @var \Drupal\tmgmt_memory\UsageStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage('tmgmt_memory_usage');
    $source_usage = $storage->loadMultipleBySegment($this->getSourceId());
    $target_usage = $storage->loadMultipleBySegment($this->getTargetId());
    /** @var \Drupal\tmgmt_memory\UsageTranslationStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage('tmgmt_memory_usage_translation');
    $usage_translations = $storage->loadMultipleBySourcesAndTargets($source_usage, $target_usage);
    /** @var \Drupal\tmgmt_memory\UsageTranslationInterface $usage_translation */
    foreach ($usage_translations as $usage_translation) {
      $usage_translation->setState($state);
      $usage_translation->save();
    }
    return NULL;
  }

}
