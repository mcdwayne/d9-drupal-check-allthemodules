<?php

/**
 * @file
 * Contains \Drupal\cronpub\Entity\CronpubEntity.
 */

namespace Drupal\cronpub\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\cronpub\Plugin\Cronpub\CronpubActionManager;
use Drupal\user\UserInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemList;
use Drupal\cronpub\CronpubEntityInterface;
use Drupal\cronpub\CronpubIcalService;

/**
 * Defines the Cronpub Task entity.
 *
 * @ingroup cronpub
 *
 * @ContentEntityType(
 *   id = "cronpub_entity",
 *   label = @Translation("Cronpub Task"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\cronpub\CronpubEntityListBuilder",
 *     "views_data" = "Drupal\cronpub\Entity\CronpubEntityViewsData",
 *
 *     "form" = {
 *       "delete" = "Drupal\cronpub\Entity\Form\CronpubEntityDeleteForm",
 *     },
 *     "access" = "Drupal\cronpub\CronpubEntityAccessControlHandler",
 *   },
 *   base_table = "cronpub_entity",
 *   admin_permission = "administer CronpubEntity entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "entity_type" = "entity_type",
 *     "entity_id" = "entity_id",
 *     "start" = "start-date",
 *     "end" = "end-date",
 *     "plugin" = "plugin",
 *     "rrule" = "rrule",
 *     "vevent" = "vevent",
 *     "chronology" = "chronology"
 *   },
 *   links = {
 *     "canonical" = "/admin/cronpub_tasks/{cronpub_entity}",
 *     "delete-form" = "/admin/cronpub_tasks/{cronpub_entity}/delete"
 *   },
 * )
 */
class CronpubEntity extends ContentEntityBase implements CronpubEntityInterface {
  use EntityChangedTrait;

  /**
   * @var \Drupal\cronpub\Plugin\Cronpub\CronpubActionManager
   */
  private $plugin_manager;

  /**
   * @var \Drupal\Core\Entity\EntityInterface;
   */
  private $target_entity;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->getValue();
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
   * Get the plugin manager for Cronpub plugins.
   * @return \Drupal\cronpub\Plugin\Cronpub\CronpubActionManager
   */
  public function getPluginManager() {
    if (!$this->plugin_manager instanceof CronpubActionManager) {
      $this->plugin_manager = \Drupal::service('plugin.manager.cronpub');
    }
    return $this->plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetType() {
    $type = $this->get('entity_type')->getValue();
    if (count($type)) {
      return $type[0]['value'];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetType($entity_type) {
    $this->set('entity_type', (string) $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetId() {
    $id = $this->get('entity_id')->getValue();
    if (count($id)) {
      return $id[0]['value'];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetId($entity_id) {
    $this->set('entity_id', (int) $entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    $id = $this->get('plugin')->getValue();
    return $id[0]['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $id = $this->getPlugin();
    return $this->getPluginManager()->getDefinition($id);
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($plugin) {
    $this->set('plugin', $plugin);
  }

  /**
   * Returns the entity the cronpub actions are working on.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getTargetEntity() {
    if (isset($this->target_entity)) {
      return $this->target_entity;
    }
    else {
      $target_type = $this->getTargetType();
      $target_id = $this->getTargetId();
      if($target_type && $target_id) {
        $this->target_entity = $this->entityTypeManager()
          ->getStorage($target_type)
          ->load($target_id);
        return $this->target_entity;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setChronology(array $values) {
    $data = [];
    foreach ($values as $key => $value) {
      if (
        in_array($value['job'], ['start', 'end'])
        && ((int) $key > 1000000000)
        && ((int) $key < 1000000000000)
      ) {
        $data[$key] = $value;
      }
    }
    $this->set('chronology', serialize($data));
  }

  /**'
   * {@inheritdoc}
   */
  public function getChronology() {
    $chronology = $this->get('chronology')->getValue();
    if (count($chronology)) {
      return reset($chronology);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function selfTest() {
    $dates = $this->getChronology();
    foreach ($dates as $data) {
      // Check chronology if there are any jobs left to execute in future.
      if (in_array($data['state'], ['pending', 'repeat'])) {
        return TRUE;
      }
    }
    // If not delete entity to keep the crons performance.
    $this->delete();
    return FALSE;
  }

  /**
   * Get a Cronpub Entity for a node if exists, else create new.
   *
   * @param string $type
   *   For example node, paragraph.
   * @param int $id
   *   Id of the entity to handle.
   * @param string $plugin
   *   Id of the entity to handle.
   * @param string $field_name
   *   The field name the cp entity was build from.
   *
   * @return CronpubEntity.
   *   Instance of this class.
   */
  public static function getCronpubEntity($type, $id, $plugin, $field_name) {
    $params = [
      'entity_type' => $type,
      'entity_id' => $id,
      'plugin' => $plugin,
      'field_name' => $field_name,
    ];
    $cronpub = \Drupal::entityTypeManager()
      ->getStorage('cronpub_entity')->loadByProperties($params);
    if (!$cronpub) {
      $cronpub = CronpubEntity::create($params);
    }
    else {
      $cronpub = reset($cronpub);
    }
    return $cronpub;
  }


  /**
   * Get a Cronpub Entity for a node if exists, else create new.
   *
   * @param string $type
   *   For example node, paragraph.
   * @param int $id
   *   Id of the entity to handle.
   *
   * @return CronpubEntity.
   *   Instance of this class.
   */
  public static function deleteCronpubEntities($type, $id) {
    $cronpub_entities = \Drupal::entityTypeManager()
      ->getStorage('cronpub_entity')->loadByProperties([
        'entity_type' => $type,
        'entity_id' => $id,
      ]);
    foreach ($cronpub_entities as $cronpub_entity) {
      $cronpub_entity->delete();
    }
  }

  /**
   * Get the field content and write jobs to the content entity.
   *
   * @param FieldItemList $cronpub_field
   *    Field of type cronpub_field_type.
   *
   * @return int
   *   Returns the id of the current entity for saving in related field.
   */
  public function editChronology(FieldItemList $cronpub_field) {
    // Prepare the existing data from CronpubEntity for testing.
    $config_data = $this->getChronology();
    foreach ($config_data as $key => $data) {
      $config_data[$key]['verified'] = FALSE;
    }
    // Collect chronology data from field.
    $cronpub_field_values = $cronpub_field->getValue();
    $collected_dates = $this->collectChronologyValues($cronpub_field_values);

    // Update the (possibly) existing chronology data with field data.
    $this->updateChronology($config_data, $collected_dates);
    $this->clearChronology($config_data);
    if (count($config_data)) {
      $this->setChronology($config_data);
      $this->save();
    }
    else {
      $this->delete();
    }
  }

  /**
   * Set multiple values of timed un-/publishing data.
   *
   * @param array $cronpub_field_values
   *   An array with publishing values (start, end) to.
   *
   * @return array
   *   An uncleaned array with all data from field definition.
   */
  private function collectChronologyValues(array $cronpub_field_values) {
    $collection = [];
    foreach ($cronpub_field_values as $field_value) {
      if (isset($field_value['rrule']) && $field_value['rrule']) {
        // Get ical calculated values.
        $rule = new CronpubIcalService($field_value);
        $alms = $rule->getDates();
      }
      else {
        $alms = [];
        // Use conventional values.
        foreach ([
                   'start' => $field_value['start'],
                   'end' => $field_value['end'],
                 ] as $key => $val)
        {
          if ($val instanceof DrupalDateTime) {
            // DrupalDateTime supports method getTimestamp.
            $timestamp = $val->getTimestamp();
            $alms[$timestamp] = [];
            $alms[$timestamp]['state'] = 'pending';
            $alms[$timestamp]['job'] = $key;
            $alms[$timestamp]['verified'] = TRUE;
          }
          else continue;
        }
      }
      $collection += $alms;
    }
    return $collection;
  }

  /**
   * Compare and update the chronology by keeping the state of existing data.
   *
   * @param array $existing
   *   The existing dates from CronpubEntity.
   * @param array $collected
   *   The collected dates from CronpubField.
   */
  private function updateChronology(array &$existing, array $collected) {
    foreach ($collected as $key => $value) {
      if (array_key_exists($key, $existing) && $existing[$key]['job'] == $value['job']) {
        $existing[$key]['verified'] = TRUE;
      }
      else {
        $existing[$key] = $value;
      }
    }
  }

  /**
   * Generate a clear history when to un-/publish an item.
   *
   * @param array $config_data
   *    The raw data to clear.
   */
  private function clearChronology(array &$config_data) {
    // Sort array by key.
    ksort($config_data);
    // Remove not verified values (From older revisions of the content entity).
    foreach ($config_data as $key => $val) {
      if ($val['verified'] === FALSE) {
        unset($config_data[$key]);
      }
    }
    // Reduce data from overlapping.
    $intensity = 0;
    reset($config_data);
    $first = key($config_data);
    foreach ($config_data as $key => $val) {
      // First item.
      if ($key == $first) {
        $intensity = ($val['job'] == 'end') ? 0 : 1;
        continue;
      }
      if ($val['job'] == 'start') {
        $intensity++;
        if ($intensity >= 2) {
          unset($config_data[$key]);
        }
      }
      elseif ($val['job'] == 'end') {
        $intensity--;
        if ($intensity >= 1 || $intensity < 0) {
          unset($config_data[$key]);
        }
        $intensity = ($intensity < 0) ? 0 : $intensity;
      }
    }
    // Reduce data from past.
    $state = \Drupal::state();
    $cron_last = $state->get('system.cron_last');

    foreach ($config_data as $key => $val) {
      if ($key < $cron_last && ($val['state'] == 'pending')) {
        $config_data[$key]['state'] = 'outdated';
      }
      else {
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Cronpub Task entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Cronpub Task entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Cronpub Task entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Cronpub Task entity.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The type of entity the cronpub task refers to.'));

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity id'))
      ->setDescription(t('The ID of entity the cronpub task refers to.'));

    $fields['plugin'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plugin'))
      ->setDescription(t('The action-plugin to use for cronpub tasks.'));

    $fields['field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Field Name'))
      ->setDescription(t('The field name the cp entity was generated from.'));

    $fields['chronology'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Chronology'))
      ->setDescription(t('A serialized array of th chronology.'));

    return $fields;
  }

}
