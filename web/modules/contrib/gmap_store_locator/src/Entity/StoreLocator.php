<?php

namespace Drupal\store_locator\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Store locator entity.
 *
 * @ingroup store_locator
 *
 * @ContentEntityType(
 *   id = "store_locator",
 *   label = @Translation("Store locator"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\store_locator\StoreLocatorListBuilder",
 *     "form" = {
 *       "default" = "Drupal\store_locator\Form\StoreLocatorForm",
 *       "add" = "Drupal\store_locator\Form\StoreLocatorForm",
 *       "edit" = "Drupal\store_locator\Form\StoreLocatorForm",
 *       "delete" = "Drupal\store_locator\Form\StoreLocatorDeleteForm",
 *     },
 *     "access" = "Drupal\store_locator\StoreLocatorAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\store_locator\StoreLocatorHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "store_locator",
 *   admin_permission = "administer store locator entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/store_locator/{store_locator}",
 *     "add-form" = "/store_locator/add",
 *     "edit-form" = "/store_locator/{store_locator}/edit",
 *     "delete-form" = "/store_locator/{store_locator}/delete",
 *     "collection" = "/store_locator/list",
 *   },
 *   field_ui_base_route = "store_locator.settings"
 * )
 */
class StoreLocator extends ContentEntityBase implements StoreLocatorInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['user_id' => \Drupal::currentUser()->id()];
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
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
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
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')->setLabel(t('ID'))->setDescription(t('The ID of the Store entity.'))->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')->setLabel(t('UUID'))->setDescription(t('The UUID of the Store entity.'))->setReadOnly(TRUE);

    // Name field for the contact.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.
    $fields['name'] = BaseFieldDefinition::create('string')->setLabel(t('Name'))->setRequired(TRUE)->setDescription(t('Enter the name'))->setSettings([
      'default_value' => '',
      'max_length' => 255,
      'text_processing' => 0,
    ])->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -10,
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);

    $fields['city'] = BaseFieldDefinition::create('string')->setLabel(t('City'))->setRequired(TRUE)->setDescription(t('Enter the city name'))->setSettings([
      'default_value' => '',
      'max_length' => 32,
      'text_processing' => 0,
    ])->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -9,
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);

    $fields['address_one'] = BaseFieldDefinition::create('string')->setLabel(t('Address One'))->setRequired(TRUE)->setDescription(t('Enter the address one detail.'))->setSettings([
      'default_value' => '',
      'max_length' => 255,
      'text_processing' => 0,
    ])->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -8,
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);

    $fields['address_two'] = BaseFieldDefinition::create('string')->setLabel(t('Address Two'))->setDescription(t('Enter the address two detail.'))->setSettings([
      'default_value' => '',
      'max_length' => 255,
      'text_processing' => 0,
    ])->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -7,
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);

    $fields['postcode'] = BaseFieldDefinition::create('string')->setLabel(t('Postcode'))->setRequired(TRUE)->setDescription(t('Enter the postcode'))->setSettings([
      'default_value' => '',
      'max_length' => 20,
      'text_processing' => 0,
    ])->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -6,
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);

    $fields['website'] = BaseFieldDefinition::create('uri')->setLabel(t('Website URL'))->setDescription(t('Enter Website URL'))->setSettings([
      'default_value' => '',
      'max_length' => 255,
      'text_processing' => 0,
    ])->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
    ])->setDisplayOptions('form', [
      'type' => 'uri',
      'weight' => -5,
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);

    $fields['logo'] = BaseFieldDefinition::create('image')->setLabel(t('Logo'))->setDescription(t('Upload logo'))->setSettings([
      'alt_field' => 0,
      'alt_field_required' => 0,
      'text_processing' => 0,
    ])->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'image',
    ])->setDisplayOptions('form', [
      'type' => 'image_image',
      'weight' => -4,
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);

    $fields['latitude'] = BaseFieldDefinition::create('string')->setLabel(t('Latitude'))->setSettings([
      'default_value' => '',
      'max_length' => 32,
      'text_processing' => 0,
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => 90,
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);

    $fields['longitude'] = BaseFieldDefinition::create('string')->setLabel(t('Longitude'))->setSettings([
      'default_value' => '',
      'max_length' => 32,
      'text_processing' => 0,
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => 90,
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')->setLabel(t('User Name'))->setDescription(t('The Name of the associated user.'))->setSetting('target_type', 'user')->setSetting('handler', 'default')->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')->setLabel(t('Publishing status'))->setDescription(t('A boolean indicating whether the Store locator is published.'))->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')->setLabel(t('Language code'))->setDescription(t('The language code of Store Locator entity.'));

    $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Created'))->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'))->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
