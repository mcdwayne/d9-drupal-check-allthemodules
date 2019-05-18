<?php

namespace Drupal\buffer_schedule\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Schedule entity.
 *
 * @ingroup buffer_schedule
 *
 * @ContentEntityType(
 *   id = "schedule",
 *   label = @Translation("Schedule"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\buffer_schedule\ScheduleListBuilder",
 *     "views_data" = "Drupal\buffer_schedule\Entity\ScheduleViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\buffer_schedule\Form\ScheduleForm",
 *       "add" = "Drupal\buffer_schedule\Form\ScheduleForm",
 *       "edit" = "Drupal\buffer_schedule\Form\ScheduleForm",
 *       "delete" = "Drupal\buffer_schedule\Form\ScheduleDeleteForm",
 *     },
 *     "access" = "Drupal\buffer_schedule\ScheduleAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\buffer_schedule\ScheduleHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "schedule",
 *   admin_permission = "administer schedule entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/schedule/schedule/{schedule}",
 *     "add-form" = "/admin/content/schedule/schedule/add",
 *     "edit-form" = "/admin/content/schedule/schedule/{schedule}/edit",
 *     "delete-form" = "/admin/content/schedule/schedule/{schedule}/delete",
 *     "collection" = "/admin/content/schedule/schedule",
 *   },
 *   field_ui_base_route = "schedule.settings"
 * )
 */
class Schedule extends ContentEntityBase implements ScheduleInterface {

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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Schedule entity.'))
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
      ->setDescription(t('The name of the Schedule entity.'))
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Schedule is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['settings'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Settings'))
      ->setDescription(t('The settings for publishing schedule entities.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['buffer'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Buffer'))
      ->setDescription(t('The list of entities to publish over time.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Schedule is published.'))
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

    return $fields;
  }

  /**
  * returns the setting value for this buffer.
  */
  public function getSettings() {
    return $this->get('settings')->getValue()[0];
  }

  /**
  * Publish the next item in the buffer.
  */
  public function publishBuffer() {
    $settings = $this->getSettings();

    switch($settings['schedule_type']) {
      case 'interval':
        $lastRan = $settings['interval_last_ran'];
        $publishedCount = 0;
        if ( ( strtotime(date('m/d/Y')) - $lastRan ) > strtotime("+" . $settings['interval_time'], date('m/d/y h:i', $lastRan)) ) {
          if (isset($settings['interval_last_published'])) {
              for ($x = $settings['interval_last_published']; $x < ($settings['interval_last_published'] + $settings['publish_amount']); $x++) {
                if (isset($this->get('buffer')->getValue()[$x])) {
                  if ($entity = entity_load('node', $this->get('buffer')->getValue()[$x]['target_id'])) {
                    $entity->setPublished(TRUE);
                    $entity->save();
                    $publishedCount++;
                  }
                }
              }
              $settings['interval_last_published'] += $publishedCount;
          }
          else {
            for ($x = 0; $x < $settings['publish_amount']; $x++) {
              if (isset($this->get('buffer')->getValue()[$x])) {
                if ($entity = entity_load('node', $this->get('buffer')->getValue()[$x]['target_id'])) {
                  $entity->setPublished(TRUE);
                  $entity->save();
                  $publishedCount++;
                }
              }
            }
            $settings['interval_last_published'] = $publishedCount;
          }

          $settings['interval_lat_ran'] = time();
          $this->settings = $settings;
          $this->save();
        }

      break;
      case 'days_of_week':
        if ( $settings['day_of_week'][date('L')] && (date('m/d/Y') !== date('m/d/Y', $settings['interval_last_ran'])) ) {
          $publishedCount = 0;
          if (isset($settings['interval_last_published'])) {
              for ($x = $settings['interval_last_published']; $x < ($settings['interval_last_published'] + $settings['publish_amount']); $x++) {
                if (isset($this->get('buffer')->getValue()[$x])) {
                  if ($entity = entity_load('node', $this->get('buffer')->getValue()[$x]['target_id'])) {
                    $entity->setPublished(TRUE);
                    $entity->save();
                    $publishedCount++;
                  }
                }
              }
              $settings['interval_last_published'] += $publishedCount;
          }
          else {
            for ($x = 0; $x < $settings['publish_amount']; $x++) {
              if (isset($this->get('buffer')->getValue()[$x])) {
                if ($entity = entity_load('node', $this->get('buffer')->getValue()[$x]['target_id'])) {
                  $entity->setPublished(TRUE);
                  $entity->save();
                  $publishedCount++;
                }
              }
            }
            $settings['interval_last_published'] = $publishedCount;
          }
          $settings['interval_lat_ran'] = time();
          $this->settings = $settings;
          $this->save();
        }
      break;
    }

  }

}
