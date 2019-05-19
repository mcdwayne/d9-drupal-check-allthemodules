<?php
/**
 * @file
 * Contains \Drupal\zsm_system_load\Entity\ZSMSystemLoadPlugin.
 */

namespace Drupal\zsm_system_load\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\zsm_system_load\ZSMSystemLoadPluginInterface;
use Drupal\zsm\ZSMUtilities;

/**
 * Defines the ZSMSystemLoadPlugin entity.
 *
 * @ingroup zsm_system_load
 *
 *
 * @ContentEntityType(
 * id = "zsm_system_load_plugin",
 * label = @Translation("ZSM System Load Plugin Settings"),
 * handlers = {
 *   "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *   "list_builder" = "Drupal\zsm_system_load\Entity\Controller\ZSMSystemLoadPluginListBuilder",
 *   "views_data" = "Drupal\zsm_system_load\ZSMSystemLoadPluginViewsData",
 *   "form" = {
 *     "add" = "Drupal\zsm_system_load\Form\ZSMSystemLoadPluginForm",
 *     "edit" = "Drupal\zsm_system_load\Form\ZSMSystemLoadPluginForm",
 *     "delete" = "Drupal\zsm_system_load\Form\ZSMSystemLoadPluginDeleteForm",
 *   },
 *   "access" = "Drupal\zsm_system_load\ZSMSystemLoadPluginAccessControlHandler",
 * },
 * list_cache_contexts = { "user" },
 * base_table = "zsm_system_load_plugin",
 * admin_permission = "administer zsm_system_load_plugin entity",
 * fieldable = TRUE,
 * entity_keys = {
 *   "id" = "id",
 *   "uuid" = "uuid",
 *   "uid" = "uid",
 *   "label" = "title",
 * },
 * links = {
 *   "canonical" = "/zsm_system_load_plugin/{zsm_system_load_plugin}",
 *   "edit-form" = "/zsm_system_load_plugin/{zsm_system_load_plugin}/edit",
 *   "delete-form" = "/zsm_system_load_plugin/{zsm_system_load_plugin}/delete",
 *   "collection" = "/zsm_system_load_plugin/list"
 * },
 * field_ui_base_route = "zsm_system_load.zsm_system_load_plugin_settings",
 * )
 *
 * @ZSMPlugin (
 *   id = "zsm_system_load_plugin",
 *   label = @Translation("System Load Monitor")
 * )
 */
class ZSMSystemLoadPlugin extends ContentEntityBase
{

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values)
  {
    parent::preCreate($storage_controller, $values);
    // Default author to current user.
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime()
  {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner()
  {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId()
  {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid)
  {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account)
  {
    $this->set('user_id', $account->id());
    return $this;
  }


  /**
   * ZSM-specific plugin data
   */
  public function getZSMPluginData()
  {
    return array(
      'class' => 'LinuxSystemLoad',
      'type' => 'core',
      'module' => 'system_monitors.linux_system_load',
    );
  }

  /**
   * ZSM-specific plugin settings
   */
  public function getZSMPluginSettings()
  {
    $data = $this->getZSMPluginData();

    $field_map = \Drupal::service('entity_field.manager')->getFieldDefinitions('zsm_system_load_plugin', 'zsm_system_load_plugin');
    // Clean out DB items that do not go into the settings
    $field_map = array_keys($field_map);
    $exclude = ['id', 'uuid', 'title', 'user_id', 'created', 'changed', 'description'];
    $field_map = array_diff($field_map, $exclude);
    foreach ($field_map as $key) {
      if ($val = $this->get($key)->getValue()) {
        if (isset($val[0]['value'])) {
          $val = $val[0]['value'];
          switch ($key) {
            default:
              $data['settings'][$key] = $val;
          }
        }
        // Handle the section-list field
        else if (isset($val[0]['section']) && isset($val[0]['list'])) {
          $dat = ZSMUtilities::digestSectionListField($val);
          foreach ($dat as $dkey => $value) {
            switch ($key) {
              default:
                $data['settings'][$dkey] = $value;
            }
          }
        }
        // Handle the alert field
        else if ($key === 'field_zsm_system_load_thresholds') {
          $ret = array();
          foreach ($val as $item) {
            $r = array();
            $type = $item['type'];
            if (isset($item['amount']) && !empty($item['amount'])) {
              $r['amount'] = $item['amount'];
              if($item['severity'] === 'custom') {
                $r['severity'] = $item['severity_custom'] ? $item['severity_custom'] : 'notice';
              }
              else {
                $r['severity'] = $item['severity'];
              }
              $data['settings']['alert_thresholds'][$type][$r['severity']] = $r['amount'];
            }
          }
        }
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the ZSM SystemLoad Plugin entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Contact entity.'))
      ->setReadOnly(TRUE);

    // Settings Title
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Title of Item'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Description
    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Short description of ZSM SystemLoad Plugin Instance'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Owner field of the contact.
    // Entity reference field, holds the reference to the user object.
    // The view shows the user name field of the user.
    // The form presents a auto complete field for the user name.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The ID of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'author',
        'weight' => -3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ),
        'weight' => -3,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }
}