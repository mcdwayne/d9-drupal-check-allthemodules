<?php

namespace Drupal\formassembly\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the FormAssembly Form entity.
 *
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2018 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 * @package Drupal\formassembly
 *
 * @ContentEntityType(
 *   id = "fa_form",
 *   label = @Translation("FormAssembly Form"),
 *   handlers = {
 *     "view_builder" =
 *   "Drupal\formassembly\Entity\FormAssemblyEntityViewBuilder",
 *     "list_builder" = "Drupal\formassembly\FormAssemblyEntityListBuilder",
 *     "views_data" = "Drupal\formassembly\Entity\FormAssemblyEntityViewsData",
 *     "storage" = "Drupal\formassembly\FormAssemblyStorage",
 *     "form" = {
 *       "default" = "Drupal\formassembly\Form\FormAssemblyEntityForm",
 *       "add" = "Drupal\formassembly\Form\FormAssemblyEntityForm",
 *       "edit" = "Drupal\formassembly\Form\FormAssemblyEntityForm",
 *       "delete" = "Drupal\formassembly\Form\FormAssemblyEntityDeleteForm",
 *     },
 *     "access" = "Drupal\formassembly\FormAssemblyEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\formassembly\FormAssemblyEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "fa_form",
 *   admin_permission = "administer formassembly form entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/formassembly/fa_form/{fa_form}",
 *     "add-form" = "/formassembly/fa_form/add",
 *     "edit-form" = "/formassembly/fa_form/{fa_form}/edit",
 *     "delete-form" = "/formassembly/fa_form/{fa_form}/delete",
 *     "collection" = "/formassembly/fa_form",
 *   },
 *   field_ui_base_route = "fa_form.settings"
 * )
 */
class FormAssemblyEntity extends ContentEntityBase implements FormAssemblyEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(
    EntityStorageInterface $storage_controller,
    array &$values
  ) {
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
  public function getModifiedTime() {
    return $this->get('modified')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setModifiedTime($timestamp) {
    $this->set('modified', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    $this->set('status', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    $this->set('status', FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type
  ) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(
        t('The name of the FormAssembly Form as it is keyed by FormAssembly. Will be overwritten on sync if it is changed in Drupal.')
      )
      ->setSettings(
        [
          'max_length' => 255,
          'text_processing' => 0,
        ]
      )
      ->setDefaultValue('')
      ->setDisplayOptions(
        'view',
        [
          'label' => 'above',
          'type' => 'string',
          'weight' => -4,
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('A boolean indicating if the form is enabled.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE)
      ->setInitialValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 100,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setTranslatable(FALSE)
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setTranslatable(FALSE)
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited in Drupal.'));

    $fields['faid'] = BaseFieldDefinition::create('integer')
      ->setTranslatable(FALSE)
      ->setLabel(t('FormAssembly ID'))
      ->setDescription(
        t('Unique key assigned by FormAssembly to identify each form')
      )
      ->setReadOnly(TRUE);

    $fields['modified'] = BaseFieldDefinition::create('timestamp')
      ->setTranslatable(FALSE)
      ->setLabel(t('Modified in FormAssembly'))
      ->setDescription(
        t('The timestamp this form was last changed in FormAssembly.')
      )
      ->setDisplayOptions(
        'view',
        [
          'region' => 'hidden',
        ]
      )
      ->setDisplayConfigurable('view', FALSE)
      ->setReadOnly(TRUE);

    $fields['query_params'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Query Parameters'))
      ->setDisplayOptions(
        'view',
        [
          'region' => 'hidden',
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'map_assoc_widget',
          'region' => 'content',
          'settings' => [
            'size' => '40',
            'key_placeholder' => 'The tfa identifier',
            'value_placeholder' => 'Pre-filled value',
          ],
          'weight' => 90,
        ]
      )
      ->setTranslatable(FALSE)
      ->setDescription(
        'Enter parameters to be added to FormAssembly form request.<br />' .
        'The <em>tfa indentifier</em> string is the name property on the field\'s input tag.'
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    return $fields;
  }

}
