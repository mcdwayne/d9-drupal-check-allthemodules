<?php

namespace Drupal\formazing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Result formazing entity entity.
 *
 * @ingroup formazing
 *
 * @ContentEntityType(
 *   id = "result_formazing_entity",
 *   label = @Translation("Result formazing entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\formazing\ResultFormazingEntityListBuilder",
 *     "translation" = "Drupal\formazing\ResultFormazingEntityTranslationHandler",
 *     "views_data" = "Drupal\formazing\Entity\ResultFormazingEntityViewsData",

 *     "form" = {
 *       "default" = "Drupal\formazing\Form\ResultFormazingEntityForm",
 *       "add" = "Drupal\formazing\Form\ResultFormazingEntityForm",
 *       "edit" = "Drupal\formazing\Form\ResultFormazingEntityForm",
 *       "delete" = "Drupal\formazing\Form\ResultFormazingEntityDeleteForm",
 *     },
 *     "access" = "Drupal\formazing\ResultFormazingEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\formazing\ResultFormazingEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "result_formazing_entity",
 *   data_table = "result_formazing_entity_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer result formazing entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/result_formazing_entity/{result_formazing_entity}",
 *     "add-form" = "/admin/structure/result_formazing_entity/add",
 *     "edit-form" = "/admin/structure/result_formazing_entity/{result_formazing_entity}/edit",
 *     "delete-form" = "/admin/structure/result_formazing_entity/{result_formazing_entity}/delete",
 *     "collection" = "/admin/structure/result_formazing_entity",
 *   },
 *   field_ui_base_route = "result_formazing_entity.settings"
 * )
 */
class ResultFormazingEntity extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(
    EntityStorageInterface $storage_controller, array &$values
  ) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
                                            ->setLabel(t('Authored by'))
                                            ->setDescription(t('The user ID of author of the Result formazing entity entity.'))
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

    $fields['form_type'] = BaseFieldDefinition::create('string')
                                              ->setLabel(t('form_type'))
                                              ->setDescription(t('The id of the form type'))
                                              ->setSettings([
                                                'max_length' => 50,
                                                'text_processing' => 0,
                                              ])
                                              ->setDefaultValue('')
                                              ->setDisplayOptions('view', [
                                                'label' => 'above',
                                                'type' => 'string',
                                                'weight' => -3,
                                              ])
                                              ->setDisplayOptions('form', [
                                                'type' => 'string_textfield',
                                                'weight' => -3,
                                              ])
                                              ->setDisplayConfigurable('form', TRUE)
                                              ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
                                         ->setLabel(t('Name'))
                                         ->setDescription(t('The name of the Result formazing entity entity.'))
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
                                         ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
                                           ->setLabel(t('Publishing status'))
                                           ->setDescription(t('A boolean indicating whether the Result formazing entity is published.'))
                                           ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Created'))->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
                                            ->setLabel(t('Changed'))
                                            ->setDescription(t('The time that the entity was last edited.'));

    $fields['data'] = BaseFieldDefinition::create('string')
                                         ->setLabel(t('Data'))
                                         ->setDescription(t('Data of form encoded'))
                                         ->setRequired(TRUE)
                                         ->setTranslatable(TRUE)
                                         ->setSettings(array(
                                           'default_value' => '',
                                           'max_length' => 2550,
                                         ))
                                         ->setDisplayOptions('view', [
                                           'label' => 'above',
                                           'type' => 'string',
                                           'weight' => -4,
                                         ])
                                         ->setDisplayOptions('form', [
                                           'type' => 'string_textfield',
                                           'weight' => -4,
                                         ]);

    $fields['langcode'] = BaseFieldDefinition::create('string')
                                             ->setLabel(t('Langcode'))
                                             ->setDescription(t('Langcode used by the user'))
                                             ->setRequired(TRUE)
                                             ->setTranslatable(TRUE)
                                             ->setSettings(array(
                                               'default_value' => '',
                                               'max_length' => 10,
                                             ))
                                             ->setDisplayOptions('view', [
                                               'label' => 'above',
                                               'type' => 'string',
                                               'weight' => -4,
                                             ])
                                             ->setDisplayOptions('form', [
                                               'type' => 'string_textfield',
                                               'weight' => -4,
                                             ]);

    return $fields;
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
  public function getLangcode() {
    return $this->get('langcode')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLangcode($langcode) {
    $this->set('langcode', $langcode);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormType() {
    return $this->get('form_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormType($form_type) {
    $this->set('form_type', $form_type);
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

}
