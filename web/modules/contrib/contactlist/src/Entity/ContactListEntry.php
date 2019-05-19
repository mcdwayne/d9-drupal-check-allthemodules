<?php

namespace Drupal\contactlist\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the content entity class.
 *
 * @ContentEntityType(
 *   id = "contactlist_entry",
 *   label = @Translation("Contact list entry"),
 *   handlers = {
 *     "access" = "Drupal\contactlist\Access\ContactListEntryAccessHandler",
 *     "list_builder" = "Drupal\contactlist\ContactListBuilder",
 *     "views_data" = "Drupal\contactlist\ContactListViewsData",
 *     "form" = {
 *       "default" = "Drupal\contactlist\Form\ContactListEntryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *   },
 *   admin_permission = "administer contact lists",
 *   base_table = "contactlist_entry",
 *   data_table = "contactlist_entry_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "clid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "label" = "name",
 *     "revision" = "cvid"
 *   },
 *   links = {
 *     "canonical" = "/contactlist/{contactlist_entry}/view",
 *     "edit-form" = "/contactlist/{contactlist_entry}/edit",
 *     "delete-form" = "/contactlist/{contactlist_entry}/delete",
 *     "collection" = "/contactlist",
 *   },
 *   field_ui_base_route = "contactlist.admin_form",
 * )
 */
class ContactListEntry extends ContentEntityBase implements ContactListEntryInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getContactName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setContactName($value) {
    $this->set('name', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->get('email')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($value) {
    $this->set('email', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhoneNumber() {
    return $this->get('telephone')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPhoneNumber($value) {
    $this->set('telephone', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($value) {
    $this->set('created', $value);
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
  public function getGroups() {
    $groups = [];
    foreach ($this->get('groups') as $reference) {
      $groups[] = $reference->entity;
    }
    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroups(array $value) {
    $this->set('groups', array_values($this->ensureValidContactGroups($value)));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addGroups(array $groups) {
    $current = $this->getGroups();
    $this->set('groups', array_values(array_merge($current, $this->ensureValidContactGroups($groups))));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeGroups(array $groups) {
    $current = $this->getGroups();
    $diff_groups = array_udiff($current, $this->ensureValidContactGroups($groups),
      function(ContactGroupInterface $a, ContactGroupInterface $b) {
        return strcasecmp($a->uuid(), $b->uuid());
      });
    $this->set('groups', array_values($diff_groups));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['clid']
      ->setLabel(t('Contact ID'))
      ->setDescription(t('The unique ID of the contact.'));

    $fields['cvid']
      ->setDescription(t('The version of the update. For synchronization.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t("Full name"))
      ->setDescription(t('The full name of the contact.'))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 1,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'user_name',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t("Email"))
      ->setDescription(t('The email address of the contact.'))
      ->setDisplayOptions('form', array(
        'type' => 'email_default',
        'weight' => 5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'email_mailto',
        'weight' => 5,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->addPropertyConstraints('value', ['Email' => []]);

    $fields['telephone'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t("Telephone"))
      ->setDescription(t('The telephone number of the contact.'))
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', array(
        'type' => 'telephone_default',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'telephone_link',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Date created'))
      ->setDescription(t('The date the contact entry was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Date last modified'))
      ->setDescription(t('The date the contact entry was last modified.'));

    $fields['owner'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner ID'))
      ->setRequired(TRUE)
      ->setDescription(t('The ID of the owner of the contact.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\contactlist\Entity\ContactListEntry::getCurrentUserId');

    $fields['groups'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Contact groups'))
      ->setRequired(FALSE)
      ->setDescription(t('The contact groups this contact entry belongs to.'))
      ->setSetting('target_type', 'contact_group')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [
        'auto_create' => TRUE,
        'auto_create_bundle' => 'contactlist_entry',
      ])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'contact_group_autocomplete',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'weight' => 0,
      ));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function getHiddenFieldNames() {
    static $hidden_fields;
    if (!isset($hidden_fields)) {
      $hidden_fields = [];
      foreach (\Drupal::service('entity_field.manager')
                 ->getFieldDefinitions('contactlist_entry', 'contactlist_entry') as $field_name => $field) {
        if (!$field->isDisplayConfigurable('form') && !$field->isDisplayConfigurable('view')) {
          $hidden_fields[] = $field_name;
        }
      }
    }
    return $hidden_fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return array(\Drupal::currentUser()->id());
  }

  /**
   * Ensures all values in the specified array are contact groups.
   *
   * Strings will be converted to contact groups while other types will throw an
   * Exceptions.
   *
   * @param array $value
   *   The list of contact groups or strings to be verified.
   *
   * @return \Drupal\contactlist\Entity\ContactGroupInterface[]
   *   All the contact groups in $value.
   *
   * @throws \LogicException
   * @throws \InvalidArgumentException
   */
  protected function ensureValidContactGroups(array $value) {
    $ensured = [];
    if ($this->getOwner() === NULL || ($owner_id = $this->getOwner()->id()) === NULL) {
      throw new \LogicException('Owner not set or saved, call setOwner() first before setting, adding or removing groups.');
    }
    else {
      $group_storage = $this->entityTypeManager()->getStorage('contact_group');
      foreach ($value as $group) {
        if (is_string($group)) {
          // Load if the contact group already exists, create if it doesn't.
          $groups = $group_storage->loadByProperties([
            'name' => $group,
            'owner' => $owner_id
          ]);
          // We don't bother to save the newly created group because we depend
          // on EntityReferenceItem saving it for us when this ContactListEntry
          // is saved.
          // @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::preSave()
          $ensured[] = $groups ? reset($groups) : $group_storage->create([
            'name' => $group,
            'owner' => $owner_id
          ]);
        }
        else {
          if ($group instanceof ContactGroupInterface) {
            $ensured[] = $group;
          }
          else {
            throw new \InvalidArgumentException(sprintf('Only strings or contact group entities are allowed, %s found', get_class($group)));
          }
        }
      }
    }
    return $ensured;
  }

}
