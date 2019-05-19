<?php

namespace Drupal\visualn_iframe\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

// @todo: hash column should be indexed for performance purposes

// @todo: how to handle when the page share link is rendered at,
//   differs from initial page?
//   or multiple instances with the same hash on a page?
//
//   maybe add some config page for the module

// @todo: Do not confuse 'data' and 'settings' fields - those have diffrent
//   purposes, settings is for 'how to show' whereas 'data' is for 'what to show'
//   and can be used as alternative to 'drawing entity' case.
//   Data field allows to store even drawing config etc. or some other info
//   depending on content provider logic.



// @todo: show a message on drawing delete that it is used for iframe
//   same for blocks


/**
 * Defines the VisualN IFrame entity.
 *
 * @ingroup iframes_toolkit
 *
 * @ContentEntityType(
 *   id = "visualn_iframe",
 *   label = @Translation("VisualN IFrame"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\visualn_iframe\VisualNIFrameListBuilder",
 *     "views_data" = "Drupal\visualn_iframe\Entity\VisualNIFrameViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\visualn_iframe\Form\VisualNIFrameForm",
 *       "add" = "Drupal\visualn_iframe\Form\VisualNIFrameForm",
 *       "edit" = "Drupal\visualn_iframe\Form\VisualNIFrameForm",
 *       "delete" = "Drupal\visualn_iframe\Form\VisualNIFrameDeleteForm",
 *     },
 *     "access" = "Drupal\visualn_iframe\VisualNIFrameAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\visualn_iframe\VisualNIFrameHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "visualn_iframe",
 *   admin_permission = "administer visualn iframe entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/visualn/config/iframes/visualn_iframe/{visualn_iframe}",
 *     "add-form" = "/admin/visualn/config/iframes/visualn_iframe/add",
 *     "edit-form" = "/admin/visualn/config/iframes/visualn_iframe/{visualn_iframe}/edit",
 *     "delete-form" = "/admin/visualn/config/iframes/visualn_iframe/{visualn_iframe}/delete",
 *     "collection" = "/admin/visualn/config/iframes/visualn_iframe",
 *   },
 *   field_ui_base_route = "visualn_iframe.settings"
 * )
 */
class VisualNIFrame extends ContentEntityBase implements VisualNIFrameInterface {

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
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrawingId() {
    return $this->get('drawing_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setDrawingId($drawing_id) {
    $this->set('drawing_id', $drawing_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHash() {
    return $this->get('hash')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHash($hash) {
    $this->set('hash', $hash);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewed() {
    return $this->get('viewed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setViewed($viewed) {
    $this->set('viewed', $viewed);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation() {
    return $this->get('location')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocation($location) {
    $this->set('location', $location);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    // @todo: maybe rename to iframe_settings
    //return $this->get('settings')->value;
    if ($this->get('settings')->value) {
      return unserialize($this->get('settings')->value);
    }
    else {
      // @todo: return NULL ?
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings($settings) {
    //$this->set('settings', $settings);
    // @todo: what if empty array ?
    if ($settings) {
      $this->set('settings', serialize($settings));
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    if ($this->get('data')->value) {
      return unserialize($this->get('data')->value);
    }
    else {
      // @todo: return NULL ?
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {
    // @todo: what if empty array ?
    if ($data) {
      $this->set('data', serialize($data));
    }
    return $this;
  }

  // @todo: add getters and setters for 'displayed', 'viewed' and 'handler_key' fields

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // @todo: maybe add target_type (possibly named differently) column
    //   to allow using entity_id column with other entities types but
    //   no only as drawing_id

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the VisualN IFrame entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the VisualN IFrame entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    // @todo: status could be used to not show iframes with given hashes at all
    //   and also to be checked while creating share link to not render it in
    //   case of status == false
    //   needs to also add cache tags to the share link block to be rerendered
    //   on status change
    //   or maybe add a separate flag that would disable share links but still
    //   allow iframes (could be usefull to check if iframes are used and to completely
    //   disable them if not used or not used intensively)
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the VisualN IFrame is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    // @todo: using just drawing id limits drawing iframes only to those provided by drawing entities
    // @todo: check 'setRevisionable' property since the entity types are not revisionable
    // @todo: rename the column
    // @todo: potentially drawing_id may be empty if used in other cases
    //   relying only on settings
    $fields['drawing_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('VisualN Drawing'))
      ->setDescription(t('The entity ID of visualn drawing of the VisualN IFrame entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'visualn_drawing')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // @todo: add 'unique' requirement
    //   also setReadOnly()
    //   and non-empty
    //   check other columns
    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setDescription(t('The hash of the VisualN IFrame entity.'))
      ->setSettings([
        // @todo: check max_length (10 is default though other modules may use different lenghts)
        //   this should be the same as in visualn_iframe staged table hash column
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    // @todo: check the key name (e.g. options, date, properties, config, info, etc.)
    $fields['settings'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Settings'))
      ->setDescription(t('The settings of the VisualN IFrame entity.'))
      ->setSettings([
        // @todo: review the length, maybe increase
        //   and is it length or max_length?
        //   this should be the same as in visualn_iframe staged table hash column
        'max_length' => 2048,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    // The field is used to show whether the iframe share code was displayed
    // at least once to the end user. It is not recommended to delete iframes
    // which are embedded into third-party sites content for obvious reasons.
    // The value can be used by garbage collector to delete unused iframe entries.
    // Such entries may be created e.g. by visualn_embed module. A common case is
    // when user enables sharing for an embedded drawing but then doesn't save
    // the changes. Nevertheless a visualn_iframe entity is created when user
    // enables sharing. Such entries are never used and need to be cleared out
    // periodically.

    // @todo: review column name
    $fields['displayed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Shared link displayed status'))
      ->setDescription(t('A boolean indicating whether the VisualN IFrame hash path was displayed at least once.'))
      //->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location'))
      ->setDescription(t('The location VisualN IFrame first displayed.'))
      ->setSettings([
        'max_length' => 2048,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // @todo: Add a column for the timestamp of the last view or count of views (or both).
    //   Also track anonymous displays/views (add to settings) since it is often ok to
    //   ignore authenticated users iframes views.

    // Also there is a diffrence when a share link was displayed (even to anonymous)
    // and the iframe itself ways displayed. Since share link view doesn't
    // mean that the iframe code was used somewhere, though if used once
    // it should have iframe route views and generally it means that the iframe
    // is really used somewhere on third-party sites

    // Together with the 'viewed' value garbage collector can use the 'changed'
    // and 'created' values.
    // @todo: review column name
    $fields['viewed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('IFrame viewed status'))
      ->setDescription(t('A boolean indicating whether the VisualN IFrame was viewed at least once.'))
      //->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['implicit'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Entry created implicitly'))
      ->setDescription(t('A boolean indicating whether the VisualN IFrame entry was created implicitly.'))
      //->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    // @todo: review
    //   hide form and display, set required, set readonly
    //   see ContentEntityBase::baseFieldDefinitions()
    //   disable the field for edit
    $fields['handler_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Handler key'))
      ->setDescription(t('A key for modules iframe content provider services to identify their records.'))
      ->setSettings([
        'max_length' => 128,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    // @todo: review key name
    //   maybe convert visualn_iframe_data db schema to blob
    //   check setDisplayConfigurable() and other properties

    $fields['data'] = BaseFieldDefinition::create('visualn_iframe_data')
      ->setLabel(t('Data'))
      ->setDescription(t('Data used to create iframe main content markup.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // @todo: maybe also add uuid for target entities

    // @todo: drawing_id (i.e. target entity id) may support only numeric
    //   target ids (used by content entities) though
    //   it is ok since config entities (with string keys) can't be many
    //   and the id can be stored as part of 'data' (currently 'settings') field
    //   also use-cases show that target_id itself (as a separate column) is required in rare cases
    //   e.g. when embedding visualn_drawing entities via ckeditor



    // @todo: add 'is_default' field for default (not overridden) settings case


    // @todo: maybe to use langcode to render iframes with the same hash for different languages
    // not clear how, what and etc.

    return $fields;
  }

  // @todo: maybe move the methods into a service

  // @todo: entry_entity_type is supposed 'visualn_drawing' by default
  //   add to arguments when a field added to the entity db structure

  // @todo: this works in case-sensitive fashion though
  //   drupal handles request as case-insensitive so it works
  //   in any case (see https://www.drupal.org/project/drupal/issues/2075889)
  public static function getIFrameEntityByHash($hash) {
    $query = \Drupal::entityQuery('visualn_iframe');
    // @todo:
    //$query->condition('status', 1);
    $query->condition('hash', $hash);
    $query->range(0, 1);
    $entity_ids = $query->execute();

    if (!empty($entity_ids)) {
      $entity_id = reset($entity_ids);
      $entity = static::load($entity_id);
    }
    else {
      return FALSE;
    }

    return $entity;
  }

}
