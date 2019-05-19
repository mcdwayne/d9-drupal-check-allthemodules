<?php

/**
 * @file
 * Contains \Drupal\smartling\Entity\SmartlingSubmission.
 */

namespace Drupal\smartling\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\smartling\SmartlingSubmissionInterface;
use \Drupal\Core\Entity\EntityInterface;

/**
 * Defines the smartling entity class.
 *
 * @ContentEntityType(
 *   id = "smartling_submission",
 *   label = @Translation("Smartling submission"),
 *   handlers = {
 *     "storage" = "Drupal\smartling\SubmissionStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\smartling\SubmissionViewsData",
 *     "list_builder" = "Drupal\smartling\SubmissionListBuilder",
 *   },
 *   base_table = "smartling_submission",
 *   admin_permission = "use smartling entity translation",
 *   links = {
 *     "canonical" = "/smartling/{smartling_submission}",
 *   },
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "original_language",
 *   }
 * )
 */
class SmartlingSubmission extends ContentEntityBase implements SmartlingSubmissionInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Smartling submission ID'))
      ->setDescription(t('The smartling submission entity ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The smarting entity UUID.'))
      ->setReadOnly(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('string') //integer
      ->setLabel(t('Related entity ID'))
      ->setDescription(t('The ID of the entity of which this submission attached.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The entity type to which submission attached.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    $fields['entity_bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity bundle'))
      ->setDescription(t('The related entity bundle.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::BUNDLE_MAX_LENGTH);

    $fields['original_language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Original language'))
      ->setDescription(t('Original language code (drupal format).'))
      ->setReadOnly(TRUE);

    $fields['target_language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Target language'))
      ->setDescription(t('Target language code (drupal format).'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity title'))
      ->setDescription(t('Smartling submission entity title. Usually it is equal to related entity title.'))
      ->setReadOnly(TRUE);

    $fields['file_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Original content'))
      ->setDescription(t('File with original content.'))
      ->setReadOnly(TRUE);

    $fields['translated_file_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Translated content'))
      ->setDescription(t('File with translated content.'))
      ->setReadOnly(TRUE);

    $fields['progress'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Translation progress'))
      ->setDescription(t('Translation progress of the submission.'))
      ->setReadOnly(TRUE)
      ->setDefaultValue(0)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setSetting('suffix', ' %')
      ->setSetting('unsigned', TRUE);

    $fields['submitter'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('Author of the submission.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValue(0)
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Status'))
      ->setDescription(t('The submission status.'))
      ->setSetting('unsigned', TRUE)
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        SmartlingSubmissionInterface::QUEUED => t('Queued'),
        SmartlingSubmissionInterface::TRANSLATING => t('Translating'),
        SmartlingSubmissionInterface::TRANSLATED => t('Translated'),
        SmartlingSubmissionInterface::CHANGED => t('Changed'),
        SmartlingSubmissionInterface::FAILED => t('Failed'),
      ])
      ->setDefaultValue(SmartlingSubmissionInterface::QUEUED);

    $fields['content_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setDescription(t('The submission hash.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the submission was created.'))
      ->setReadOnly(TRUE);

    // @todo Add index via "storage_schema" annotation class.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the submission was last saved.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedEntity() {
    // @todo add at least static caching here.
    return entity_load($this->get('entity_type')->value, $this->get('entity_id')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileName() {
    $file_name = $this->get('file_name')->value;
    if (!$file_name) {
      $file_name = $this->generateFileName();
    }
    return $file_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatusByEvent($event) {
    // @todo Clean-up states and assert() allowed statuses.
    if (is_null($event)) {
      return;
    }

    $status = $this->get('status')->value;
    switch ($event) {
      case SMARTLING_STATUS_EVENT_SEND_TO_UPLOAD_QUEUE:
        if (empty($status) || ($status == SmartlingSubmissionInterface::CHANGED)) {
          $status = SmartlingSubmissionInterface::QUEUED;
        }
        break;

      case SMARTLING_STATUS_EVENT_UPLOAD_TO_SERVICE:
        $status = SmartlingSubmissionInterface::TRANSLATING;
        break;

      case SMARTLING_STATUS_EVENT_DOWNLOAD_FROM_SERVICE:
      case SMARTLING_STATUS_EVENT_UPDATE_FIELDS:
        if ($status != SmartlingSubmissionInterface::CHANGED && $this->get('progress')->value == 100) {
          $status = SmartlingSubmissionInterface::TRANSLATED;
        }
        break;

      case SMARTLING_STATUS_EVENT_NODE_ENTITY_UPDATE:
        $status = SmartlingSubmissionInterface::CHANGED;
        break;

      case SMARTLING_STATUS_EVENT_FAILED_UPLOAD:
        $status = SmartlingSubmissionInterface::FAILED;
        break;
    }

    return $this->set('status', $status);
  }

  /**
   * {@inheritdoc}
   */
  public static function getFromDrupalEntity(EntityInterface $entity, $target_langcode) {
    $submission = self::create([
      'entity_id' => $entity->id(),
      'entity_type' => $entity->getEntityType()->id(),
      'entity_bundle' => $entity->bundle(),
      'title' => $entity->label(),
      'original_language' => $entity->language()->getId(),
      'target_language' => $target_langcode,
      'submitter' => \Drupal::currentUser()->id(),
    ]);

    return $submission;
  }

  /**
   * {@inheritdoc}
   */
  public function generateFileName() {
    // @todo Add transliteration of entity title.
    $name = new FormattableMarkup('@entity_type.@entity_id.@src.xml', [
      '@entity_type' => $this->get('entity_type')->value,
      '@entity_id' => $this->get('entity_id')->value,
      '@src' => $this->language()->getId(),
    ]);
    return Unicode::strtolower($name);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultipleByConditions(array $conditions) {
    $query = \Drupal::entityQuery('smartling_submission');
    foreach ($conditions as $name => $value) {
      $query = $query->condition($name, $value);
    }

    $ids = $query->execute();
    return static::loadMultiple($ids);
  }

}
