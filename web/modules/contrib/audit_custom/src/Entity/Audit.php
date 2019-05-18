<?php

namespace Drupal\audit\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the audit entity class.
 *
 * @ContentEntityType(
 *   id = "audit",
 *   label = @Translation("审批模型"),
 *   base_table = "audit",
 *   handlers = {
 *     "list_builder" = "Drupal\audit\AuditListBuilder",
 *     "access" = "Drupal\audit\AuditAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\audit\Form\AuditForm",
 *       "overview" = "Drupal\audit\Form\AuditOverviewForm",
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid"  = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/audit/{audit}/edit",
 *   }
 * )
 */
class Audit extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if ($this->isNew()) {
      $this->set('uid', \Drupal::currentUser()->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Audit ID'))
      ->setDescription(t('The Audit ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The Audit UUID for updated.'))
      ->setReadOnly(TRUE);

    $fields['role'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('审核人员角色'))
      ->setSetting('target_type', 'user_role');

    $fields['auid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('审核人员'))
      ->setDefaultValue(0)
      ->setSetting('target_type', 'user');

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('审批状态'))
      ->setDefaultValue(0)
      ->setDescription(t('审批状态'));

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('审批顺序'))
      ->setDefaultValue(0)
      ->setDescription(t('审批顺序'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('申请人'))
      ->setDefaultValue(0)
      ->setSetting('target_type', 'user');

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('审批意见'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 15,
      ])
      ->setCardinality(-1)
      ->setDisplayConfigurable('form', TRUE);

    $fields['isaudit'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('是否审核'))
      ->setDefaultValue(0)
      ->setDescription(t('是否审核'));

    // 1.正常
    // 0.已删除.
    $fields['deleted'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('删除'))
      ->setDefaultValue(1)
      ->setDescription(t('是否删除'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The audit was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The audit was last edited..'));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code.'));

    return $fields;
  }

}
