<?php

namespace Drupal\cloudconvert\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\file\FileInterface;
use Drupal\user\UserInterface;

/**
 * Defines the CloudConvert Task entity.
 *
 * @ingroup cloudconvert
 *
 * @ContentEntityType(
 *   id = "cloudconvert_task",
 *   label = @Translation("CloudConvert Task"),
 *   bundle_label = @Translation("CloudConvert Task type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\cloudconvert\CloudConvertTaskListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\cloudconvert\CloudConvertTaskAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\cloudconvert\CloudConvertTaskHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "cloudconvert_task",
 *   admin_permission = "administer cloudconvert task entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "process_id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "status" = "step",
 *   },
 *   links = {
 *     "canonical" =
 *   "/admin/config/media/cloudconvert/task/{cloudconvert_task}",
 *     "delete-form" =
 *   "/admin/config/media/cloudconvert/task/{cloudconvert_task}/delete",
 *     "collection" = "/admin/config/media/cloudconvert/task",
 *   },
 *   bundle_entity_type = "cloudconvert_task_type"
 * )
 */
class CloudConvertTask extends ContentEntityBase implements CloudConvertTaskInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storageController, array &$values) {
    parent::preCreate($storageController, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['original_file_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Original File'))
      ->setDescription(t('The File ID which the CloudConvert task wants to convert.'))
      ->setSetting('target_type', 'file')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['process_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Process ID'))
      ->setDescription(t('A string indicating what the CloudConvert Process ID.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['process_info'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Process Info'))
      ->setDescription(t('Last known information about the process from cloud convert.'))
      ->setDefaultValue([]);

    $fields['process_parameters'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Process Parameters'))
      ->setDescription(t('The parameters about how the process should be processed at cloud convert.'))
      ->setDefaultValue([]);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the CloudConvert Task entity.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['step'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Step'))
      ->setDescription(t('A string indicating what the CloudConvert step status is.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function getStep() {
    return $this->get('step')->value;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function setStep($step) {
    $this->set('step', $step);
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function getProcessId() {
    return $this->get('process_id')->value;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function setProcessId($processId) {
    $this->set('process_id', $processId);
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function getProcessInfo() {
    $processInfo = $this->get('process_info')->getValue();
    return reset($processInfo);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function setProcessInfo(array $processInfo) {
    $this->set('process_info', $processInfo);
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function setProcessParameters(array $processParameters) {
    $this->set('process_parameters', $processParameters);
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function updateProcessParameters(array $processParameters) {
    $currentParameters = $this->getProcessParameters();
    foreach ($processParameters as $key => $value) {
      $currentParameters[$key] = $value;
    }
    $this->set('process_parameters', $currentParameters);
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function getProcessParameters() {
    $processParameters = $this->get('process_parameters')->getValue();
    return reset($processParameters);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function getOriginalFileId() {
    return $this->get('original_file_id')->target_id;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function setOriginalFileId($originalFileId) {
    $this->set('original_file_id', $originalFileId);
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function getOriginalFile() {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $originalFileField */
    $originalFileField = $this->get('original_file_id');
    $files = $originalFileField->referencedEntities();
    return reset($files);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function setOriginalFile(FileInterface $originalFile) {
    $this->set('original_file_id', $originalFile->id());
    return $this;
  }

}
