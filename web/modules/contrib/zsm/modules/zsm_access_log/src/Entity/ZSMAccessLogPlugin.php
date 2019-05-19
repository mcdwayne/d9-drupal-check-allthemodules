<?php
/**
 * @file
 * Contains \Drupal\zsm_access_log\Entity\ZSMAccessLogPlugin.
 */

namespace Drupal\zsm_access_log\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\zsm_access_log\ZSMAccessLogPluginInterface;
use Drupal\zsm\ZSMUtilities;

/**
 * Defines the ZSMAccessLogPlugin entity.
 *
 * @ingroup zsm_access_log
 *
 *
 * @ContentEntityType(
 * id = "zsm_access_log_plugin",
 * label = @Translation("ZSM AccessLog Plugin Settings"),
 * handlers = {
 *   "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *   "list_builder" = "Drupal\zsm_access_log\Entity\Controller\ZSMAccessLogPluginListBuilder",
 *   "views_data" = "Drupal\zsm_access_log\ZSMAccessLogPluginViewsData",
 *   "form" = {
 *     "add" = "Drupal\zsm_access_log\Form\ZSMAccessLogPluginForm",
 *     "edit" = "Drupal\zsm_access_log\Form\ZSMAccessLogPluginForm",
 *     "delete" = "Drupal\zsm_access_log\Form\ZSMAccessLogPluginDeleteForm",
 *   },
 *   "access" = "Drupal\zsm_access_log\ZSMAccessLogPluginAccessControlHandler",
 * },
 * list_cache_contexts = { "user" },
 * base_table = "zsm_access_log_plugin",
 * admin_permission = "administer zsm_access_log_plugin entity",
 * fieldable = TRUE,
 * entity_keys = {
 *   "id" = "id",
 *   "uuid" = "uuid",
 *   "uid" = "uid",
 *   "label" = "title",
 * },
 * links = {
 *   "canonical" = "/zsm_access_log_plugin/{zsm_access_log_plugin}",
 *   "edit-form" = "/zsm_access_log_plugin/{zsm_access_log_plugin}/edit",
 *   "delete-form" = "/zsm_access_log_plugin/{zsm_access_log_plugin}/delete",
 *   "collection" = "/zsm_access_log_plugin/list"
 * },
 * field_ui_base_route = "zsm_access_log.zsm_access_log_plugin_settings",
 * )
 *
 * @ZSMPlugin (
 *   id = "zsm_access_log_plugin",
 *   label = @Translation("AccessLog")
 * )
 */
class ZSMAccessLogPlugin extends ContentEntityBase
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
      'class' => 'AccessLog',
      'type' => 'core',
      'module' => 'log_analysis.access_log',
    );
  }

  /**
   * ZSM-specific plugin settings
   */
  public function getZSMPluginSettings()
  {
    $data = $this->getZSMPluginData();

    $field_map = \Drupal::service('entity_field.manager')->getFieldDefinitions('zsm_access_log_plugin', 'zsm_access_log_plugin');
    // Clean out DB items that do not go into the settings
    $field_map = array_keys($field_map);
    $exclude = ['id', 'uuid', 'title', 'user_id', 'created', 'changed', 'description'];
    $field_map = array_diff($field_map, $exclude);
    foreach ($field_map as $key) {
      if ($val = $this->get($key)->getValue()) {
        if (isset($val[0]['value'])) {
          $val = $val[0]['value'];
          switch ($key) {
            case 'access_log_state_filename':
            case 'access_log_state_max_age':
            case 'access_log_state_save_state':
              $k = str_replace('access_log_state_', '', $key);
              $data['settings']['access_log_state'][$k] = $val;
              break;
            default:
              $data['settings'][$key] = $val;
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
      ->setDescription(t('The ID of the ZSM Access Log Plugin entity.'))
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
      ->setDescription(t('Short description of ZSM Access Log Plugin Instance'))
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

    // Description
    $fields['access_log_path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Server path to access log'))
      ->setDescription(t('Absolute path to the access log file we are monitoring. This should be in standard (not customized) access log format.'))
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

    $fields['access_log_state_filename'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Save State Filename'))
      ->setDescription(t('File name of the "save state" file. This will go into the User Data folder of your ZSM instance.'))
      ->setSettings(array(
        'default_value' => 'access_log_state.json',
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
    $fields['access_log_state_save_state'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Save on Shutdown?'))
      ->setDescription(t('Enable this if you want to save your data for the next run.'))
      ->setSettings(array(
        'default_value' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'select',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['access_log_state_max_age'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Max age of log items'))
      ->setDescription(t('The maximum time to keep log items, in seconds, before deleting them from the save state file. Set to 0 to keep everything.'))
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