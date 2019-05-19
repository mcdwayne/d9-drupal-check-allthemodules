<?php

namespace Drupal\contactlist\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the contact group entity.
 *
 * @ContentEntityType(
 *   id = "contact_group",
 *   label = @Translation("Contact group"),
 *   handlers = {
 *     "access" = "Drupal\contactlist\Access\ContactListEntryAccessHandler",
 *     "list_builder" = "Drupal\contactlist\ContactGroupListBuilder",
 *     "form" = {
 *       "default" = "Drupal\contactlist\Form\ContactGroupForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *   },
 *   admin_permission = "administer contact lists",
 *   base_table = "contact_groups",
 *   data_table = "contact_groups_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "cgid",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "langcode" = "langcode"
 *   },
 *   links = {
 *     "canonical" = "/contactlist/group/{contact_group}",
 *     "edit-form" = "/contactlist/group/{contact_group}",
 *     "delete-form" = "/contactlist/group/{contact_group}/delete",
 *     "collection" = "/contactlist/group",
 *   }
 * )
 */
class ContactGroup extends ContentEntityBase implements ContactGroupInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($value) {
    $this->set('name', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($value) {
    $this->set('description', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('owner')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('owner', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('owner')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('owner', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($value) {
    $this->set('weight', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContacts() {
    $storage = $this->entityTypeManager()->getStorage('contactlist_entry');
    $ids = $storage->getQuery()
      ->condition('groups', $this->id())
      ->condition('owner', $this->getOwner()->id())
      ->execute();
    return array_values($storage->loadMultiple($ids));
  }

  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    /** @var \Drupal\contactlist\Entity\ContactGroupInterface $group */
    foreach ($entities as $group) {
      // Remove all contacts in each group.
      foreach ($group->getContacts() as $contact) {
        $contact->removeGroups([$group])->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['cgid']->setLabel(t('Contact Group ID'))
      ->setDescription(t('The unique identifier for this contact group.'));

    $fields['uuid']->setDescription(t('The contact group UUID.'));

    $fields['langcode']->setDescription(t('The contact group language code.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the contact group. Though not a requirement, names would better be unique.'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('A description for the contact group.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this contact group in relation to other conteact groups.'))
      ->setDefaultValue(0);

    $fields['owner'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner ID'))
      ->setRequired(TRUE)
      ->setDescription(t('The user who owns the contact group.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\contactlist\Entity\ContactListEntry::getCurrentUserId');

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the contact group was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

}
