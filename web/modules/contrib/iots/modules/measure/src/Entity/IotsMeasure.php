<?php

namespace Drupal\iots_measure\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Iots Measure entity.
 *
 * @ingroup iots_measure
 *
 * @ContentEntityType(
 *   id = "iots_measure",
 *   label = @Translation("IOTs Measure"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\iots_measure\IotsMeasureListBuilder",
 *     "views_data" = "Drupal\iots_measure\Entity\IotsMeasureViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\iots_measure\Form\IotsMeasureForm",
 *       "add" = "Drupal\iots_measure\Form\IotsMeasureForm",
 *       "edit" = "Drupal\iots_measure\Form\IotsMeasureForm",
 *       "delete" = "Drupal\iots_measure\Form\IotsMeasureDeleteForm",
 *     },
 *     "access" = "Drupal\iots_measure\IotsMeasureAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\iots_measure\IotsMeasureHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "iots_measure",
 *   admin_permission = "administer iots measure entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "measure",
 *     "uid" = "user",
 *   },
 *   links = {
 *     "canonical" = "/iots/measure/{iots_measure}",
 *     "add-form" = "/iots/measure/add",
 *     "edit-form" = "/iots/measure/{iots_measure}/edit",
 *     "delete-form" = "/iots/measure/{iots_measure}/delete",
 *     "collection" = "/iots/measure",
 *   },
 *   field_ui_base_route = "iots_measure.settings"
 * )
 */
class IotsMeasure extends ContentEntityBase implements IotsMeasureInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('measure')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('measure', $name);
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
    return $this->get('user')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user', $account->id());
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

    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Iots Measure entity.'))
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

    $fields['channel'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Channel'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'iots_channel')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [
        'auto_create' => FALSE,
      ])
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => 'Enter here channel title...',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Iots Channel is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ]);

    $fields['period'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Period'))
      ->setDefaultValue(0)
      ->setSettings([
        'allowed_values' => [
          0 => t('second'),
          1 => t('minute'),
          2 => t('3 minute'),
          3 => t('15 minute'),
          4 => t('hour'),
          5 => t('week'),
          6 => t('month'),
          7 => t('quarter'),
          8 => t('year'),
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['measure'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Measure'))
      ->setDefaultValue(FALSE)
      ->setSettings(['precision' => 19, 'scale' => 6])
      ->setDisplayOptions('view', ['hidden' => TRUE])
      ->setDisplayOptions('form', ['hidden' => TRUE])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);
    return $fields;
  }

}
