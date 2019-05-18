<?php

namespace Drupal\discussions\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\discussions\DiscussionInterface;

/**
 * Defines the Discussion entity.
 *
 * @ingroup discussion
 *
 * @ContentEntityType(
 *   id = "discussion",
 *   label = @Translation("Discussion"),
 *   bundle_label = @Translation("Discussion Type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\discussions\Controller\DiscussionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\discussions\Form\DiscussionForm",
 *       "add" = "Drupal\discussions\Form\DiscussionForm",
 *       "edit" = "Drupal\discussions\Form\DiscussionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "add_to_group" = "Drupal\discussions\Form\GroupDiscussionForm"
 *     },
 *     "access" = "Drupal\discussions\DiscussionAccessControlHandler",
 *   },
 *   base_table = "discussions",
 *   admin_permission = "administer discussions",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "canonical" = "/discussion/{discussion}",
 *   },
 *   bundle_entity_type = "discussion_type",
 *   field_ui_base_route = "entity.discussion_type.edit_form"
 * )
 */
class Discussion extends ContentEntityBase implements DiscussionInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->subject->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the discussion creator.'))
      ->setSetting('unsigned', TRUE);

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDescription(t('The Discussion subject.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the Discussion was created.'))
      ->setRevisionable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the Discussion was last edited.'))
      ->setRevisionable(TRUE);

    return $fields;
  }

}
