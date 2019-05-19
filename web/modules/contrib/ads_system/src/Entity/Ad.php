<?php

namespace Drupal\ads_system\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\ads_system\AdInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Ad entity.
 *
 * @ingroup ads_system
 *
 * @ContentEntityType(
 *   id = "ad",
 *   label = @Translation("Ad"),
 *   bundle_label = @Translation("Ad type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ads_system\AdListBuilder",
 *     "views_data" = "Drupal\ads_system\Entity\AdViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\ads_system\Form\AdForm",
 *       "add" = "Drupal\ads_system\Form\AdForm",
 *       "edit" = "Drupal\ads_system\Form\AdForm",
 *       "delete" = "Drupal\ads_system\Form\AdDeleteForm",
 *     },
 *     "access" = "Drupal\ads_system\AdAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\ads_system\AdHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ad",
 *   admin_permission = "administer ad entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/ad/{ad}",
 *     "add-form" = "/ad/add/{ad_type}",
 *     "edit-form" = "/ad/{ad}/edit",
 *     "delete-form" = "/ad/{ad}/delete",
 *     "collection" = "/ad",
 *   },
 *   bundle_entity_type = "ad_type",
 *   field_ui_base_route = "entity.ad_type.edit_form"
 * )
 */
class Ad extends ContentEntityBase implements AdInterface {
  use EntityChangedTrait;

  /**
   * Managing the config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   *   An immutable configuration object.
   */
  protected $config;

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
  public function getType() {
    return $this->bundle();
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
   * Getting Ad sizes.
   *
   * @return mixed
   *   Values of the sizes.
   */
  public function getSize() {

    $config = \Drupal::config('ads_system.settings');
    $ad_sizes = explode("\r\n", $config->get('ad_sizes'));

    return $ad_sizes[(int) $this->values['size']['x-default']];
  }

  /**
   * Getting the Breakpoint minimum.
   *
   * @return mixed
   *   The current breakpoint min usable for Ad.
   */
  public function getBreakpointMin() {

    $config = \Drupal::config('ads_system.settings');
    $ad_breakpoints = explode("\r\n", $config->get('ad_breakpoints'));

    return $ad_breakpoints[$this->get('breakpoint_min')->value];
  }

  /**
   * Getting the Breakpoint maximum.
   *
   * @return mixed
   *   The current breakpoint max usable for Ad.
   */
  public function getBreakpointMax() {

    $config = \Drupal::config('ads_system.settings');
    $ad_breakpoints = explode("\r\n", $config->get('ad_breakpoints'));

    return $ad_breakpoints[$this->get('breakpoint_max')->value];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Ad entity.'))
      ->setReadOnly(TRUE);
    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The Ad type/bundle.'))
      ->setSetting('target_type', 'ad_type')
      ->setRequired(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Ad entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Ad entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Ad entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Ad is published.'))
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Ad entity.'))
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    // Get Ad config.
    $config = \Drupal::config('ads_system.settings');

    $fields['size'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Size'))
      ->setDescription(t('The size of the Ad entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
        'allowed_values' => explode("\r\n", $config->get('ad_sizes')),
      ])
      ->setDefaultValue('')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['breakpoint_min'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Breakpoint min'))
      ->setDescription(t('The breakpoint min of the Ad entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
        'allowed_values' => explode("\r\n", $config->get('ad_breakpoints')),
      ])
      ->setDefaultValue('')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['breakpoint_max'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Breakpoint max'))
      ->setDescription(t('The breakpoint max of the Ad entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
        'allowed_values' => explode("\r\n", $config->get('ad_breakpoints')),
      ])
      ->setDefaultValue('')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['ad_script'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Ad Script'))
      ->setDescription(t('Ad Script to render'))
      ->setSettings([
        'text_processing' => 1,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 0,
        'settings' => [
          'rows' => 10,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
