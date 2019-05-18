<?php

namespace Drupal\ansible\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Ansible entity.
 *
 * @ingroup ansible
 *
 * @ContentEntityType(
 *   id = "ansible_entity",
 *   label = @Translation("Ansible"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ansible\AnsibleEntityListBuilder",
 *     "views_data" = "Drupal\ansible\Entity\AnsibleEntityViewsData",
 *     "translation" = "Drupal\ansible\AnsibleEntityTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\ansible\Form\AnsibleEntityForm",
 *       "add" = "Drupal\ansible\Form\AnsibleEntityForm",
 *       "edit" = "Drupal\ansible\Form\AnsibleEntityForm",
 *       "delete" = "Drupal\ansible\Form\AnsibleEntityDeleteForm",
 *     },
 *     "access" = "Drupal\ansible\AnsibleEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\ansible\AnsibleEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ansible_entity",
 *   data_table = "ansible_entity_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer ansible entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/system/ansible/{ansible_entity}",
 *     "add-form" = "/admin/config/system/ansible/add",
 *     "edit-form" = "/admin/config/system/ansible/{ansible_entity}/edit",
 *     "delete-form" = "/admin/config/system/ansible/{ansible_entity}/delete",
 *     "collection" = "/admin/config/system/ansible",
 *   },
 *   field_ui_base_route = "ansible_entity.settings"
 * )
 */
class AnsibleEntity extends ContentEntityBase implements AnsibleEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {

  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
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
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Note entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Note entity.'))
      ->setReadOnly(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Ansible entity is published.'))
      ->setDefaultValue(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Ansible configuration.'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Litteral Note field.
    // ListTextType with a drop down menu widget.
    // The values shown in the menu are 'A', 'B', 'C' and 'D'.
    // In the view the field content is shown as string.
    // In the form the choices are presented as options list.
    $fields['playbook'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Playbook'))
      ->setDescription(t('The name of the Playbook. (eg : playbook.yml)'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 60,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Litteral appreciation field.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.
    $fields['tags'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tags'))
      ->setDescription(t('The name of the Tags.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 100,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['inventoryfile'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Inventory file'))
      ->setRequired(TRUE)
      ->setDescription(t("Inventory file host (eg: hosts)"))
      ->setSettings([
        'default_value' => '',
        'max_length' => 200,
        'text_processing' => 0,
        'placeholder' => 'eg : hosts',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => -4,
        'settings' => [
          'placeholder' => 'eg : hosts',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['playbookdirectory'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Playbook directory'))
      ->setDescription(t("Playbook directory (eg : /usr/local/admin/ansible)"))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 200,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['extravars'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Extravars'))
      ->setDescription(t("extravars (eg : foo=bar)"))
      ->setSettings([
        'default_value' => '',
        'max_length' => 200,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['default_entity'] = BaseFieldDefinition::create('string')
      ->setLabel(t('default'))
      ->setDescription(t('default'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {

  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {

  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {

  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {

  }

}
