<?php

namespace Drupal\audit_locale\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the audit_locale entity class.
 *
 * @ContentEntityType(
 *   id = "audit_locale",
 *   label = @Translation("审批流程配置"),
 *   base_table = "audit_locale",
 *   handlers = {
 *     "list_builder" = "Drupal\audit_locale\AuditLocaleListBuilder",
 *     "access" = "Drupal\audit_locale\AuditLocaleAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\audit_locale\Form\AuditLocaleForm",
 *       "overview" = "Drupal\audit_locale\Form\AuditLocaleOverviewForm",
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid"  = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/audit_locale/{audit_locale}/edit",
 *   }
 * )
 */
class AuditLocale extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $this->set('uid', \Drupal::currentUser()->id());
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
      ->setLabel(t('AuditLocale ID'))
      ->setDescription(t('The AuditLocale ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The AuditLocale UUID for updated.'))
      ->setReadOnly(TRUE);

    $fields['module'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Module.'))
      ->setDescription(t('待审批的模块类型'));

    $fields['module_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('模块的ID'))
      ->setDefaultValue(0)
      ->setDescription(t('模块的ID.'));

    $fields['role'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('审核人员角色'))
      ->setSetting('target_type', 'user_role')
      ->setDescription(t('审核人员角色-如果被指定的话.'));

    $fields['aid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('审核人员'))
      ->setDefaultValue(0)
      ->setSetting('target_type', 'user')
      ->setDescription(t('审核人员-如果被指定的话.'));

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('审核的权重'))
      ->setDefaultValue(0)
      ->setDescription(t('审核的权重-用于确定审批的顺序.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('申请人'))
      ->setSetting('target_type', 'user');

    // 1.正常
    // 0.已删除.
    $fields['deleted'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('删除'))
      ->setDefaultValue(1)
      ->setDescription(t('是否删除'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The audit_locale was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The audit_locale was last edited..'));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code.'));

    return $fields;
  }

}
