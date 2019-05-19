<?php

namespace Drupal\tmgmt_memory\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\tmgmt_memory\UsageTranslationInterface;

/**
 * Entity class for the tmgmt_memory_usage_translation entity.
 *
 * @ContentEntityType(
 *   id = "tmgmt_memory_usage_translation",
 *   label = @Translation("Usage Translation"),
 *   handlers = {
 *     "storage" = "Drupal\tmgmt_memory\UsageTranslationStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   module = "tmgmt_memory",
 *   base_table = "tmgmt_memory_usage_translation",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 *
 * @ingroup tmgmt_memory
 */
class UsageTranslation extends ContentEntityBase implements UsageTranslationInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['state'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('State'))
      ->setDescription(t('State of the UsageTranslation.'))
      ->setSetting('unsigned', TRUE);

    $fields['source'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Source usage'))
      ->setDescription(t('The source usage.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'tmgmt_memory_usage');

    $fields['target'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Target usage'))
      ->setDescription(t('The target usage.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'tmgmt_memory_usage');

    $fields['quality'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Quality'))
      ->setDescription(t('Quality of the translation.'))
      ->setSetting('min', 0)
      ->setSetting('max', 10);

    $fields['target_language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Target language'))
      ->setDescription(t('The target language of the usage translation.'));

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
    /** @var \Drupal\tmgmt_memory\UsageStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_usage');
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
    /** @var \Drupal\tmgmt_memory\UsageStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_usage');
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
    return $this->set('state', $state);
  }
}
