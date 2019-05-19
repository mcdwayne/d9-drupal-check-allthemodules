<?php
/**
 * @file
 * Contains \Drupal\zsm_spectra_reporter\Entity\ZSMSpectraReporterPlugin.
 */

namespace Drupal\zsm_spectra_reporter\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\zsm_spectra_reporter\ZSMSpectraReporterPluginInterface;
use Drupal\zsm\ZSMUtilities;

/**
 * Defines the ZSMSpectraReporterPlugin entity.
 *
 * @ingroup zsm_spectra_reporter
 *
 *
 * @ContentEntityType(
 * id = "zsm_spectra_reporter_plugin",
 * label = @Translation("ZSM Spectra Reporter Plugin Settings"),
 * handlers = {
 *   "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *   "list_builder" = "Drupal\zsm_spectra_reporter\Entity\Controller\ZSMSpectraReporterPluginListBuilder",
 *   "views_data" = "Drupal\zsm_spectra_reporter\ZSMSpectraReporterPluginViewsData",
 *   "form" = {
 *     "add" = "Drupal\zsm_spectra_reporter\Form\ZSMSpectraReporterPluginForm",
 *     "edit" = "Drupal\zsm_spectra_reporter\Form\ZSMSpectraReporterPluginForm",
 *     "delete" = "Drupal\zsm_spectra_reporter\Form\ZSMSpectraReporterPluginDeleteForm",
 *   },
 *   "access" = "Drupal\zsm_spectra_reporter\ZSMSpectraReporterPluginAccessControlHandler",
 * },
 * list_cache_contexts = { "user" },
 * base_table = "zsm_spectra_reporter_plugin",
 * admin_permission = "administer zsm_spectra_reporter_plugin entity",
 * fieldable = TRUE,
 * entity_keys = {
 *   "id" = "id",
 *   "uuid" = "uuid",
 *   "uid" = "uid",
 *   "label" = "title",
 * },
 * links = {
 *   "canonical" = "/zsm_spectra_reporter_plugin/{zsm_spectra_reporter_plugin}",
 *   "edit-form" = "/zsm_spectra_reporter_plugin/{zsm_spectra_reporter_plugin}/edit",
 *   "delete-form" = "/zsm_spectra_reporter_plugin/{zsm_spectra_reporter_plugin}/delete",
 *   "collection" = "/zsm_spectra_reporter_plugin/list"
 * },
 * field_ui_base_route = "zsm_spectra_reporter.zsm_spectra_reporter_plugin_settings",
 * )
 *
 * @ZSMPlugin (
 *   id = "zsm_spectra_reporter_plugin",
 *   label = @Translation("Spectra Reporter")
 * )
 */
class ZSMSpectraReporterPlugin extends ContentEntityBase
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
      'class' => 'SpectraReporter',
      'type' => 'core',
      'module' => 'report_methods.spectra_reporter',
    );
  }

  /**
   * ZSM-specific plugin settings
   */
  public function getZSMPluginSettings()
  {
    $data = $this->getZSMPluginData();

    $field_map = \Drupal::service('entity_field.manager')->getFieldDefinitions('zsm_spectra_reporter_plugin', 'zsm_spectra_reporter_plugin');
    // Clean out DB items that do not go into the settings
    $field_map = array_keys($field_map);
    $exclude = ['id', 'uuid', 'title', 'user_id', 'created', 'changed', 'description'];
    $field_map = array_diff($field_map, $exclude);
    foreach ($field_map as $key) {
      if ($val = $this->get($key)->getValue()) {
        if (isset($val[0]['value'])) {
          $val = $val[0]['value'];
          switch ($key) {
            case 'url':
            case 'plugin_name':
            case 'report_alerts':
            case 'report_reports':
              $data['settings']['report'][$key] = $val;
              break;
            case 'field_zsm_spectra_exclude':
            case 'field_zsm_spectra_require':
              $nkey = str_replace('field_zsm_spectra_', '', $key);
              $data['settings']['report'][$nkey] = $val;
              break;
            case 'auth_location':
            case 'auth_name':
            case 'auth_pass':
              $nkey = str_replace('auth_', '', $key);
              $data['settings']['auth'][$nkey] = $val;
              break;
            case 'actor_type':
            case 'object_type':
            case 'context_type':
              $arr = explode('_', $key, 2);
              $data['settings']['report'][$arr[0]]['type'] = $val;
              break;
            case 'actor_property':
            case 'object_property':
            case 'context_property':
            case 'actor_property_type':
            case 'object_property_type':
            case 'context_property_type':
              $arr = explode('_', $key, 2);
              $data['settings']['report'][$arr[0]][$arr[1]] = $val;
              break;
            default:
              $data['settings'][$key] = $val;
          }
        }
        else if ($key = 'field_grouped_data') {
          $dat = $this->digestGroupedDataField($val);
          isset($dat['group']) ? $data['settings']['report']['group'] = $dat['group'] : NULL;
          isset($dat['unique']) ? $data['settings']['report']['unique'] = $dat['unique'] : NULL;
        }
      }
    }
    return $data;
  }

  /**
   * Helper function for digesting the grouped data fields
   */
  public function digestGroupedDataField($value) {
    $ret = array();
    foreach ($value as $v) {
      $klist = explode(PHP_EOL, $v['keys']);
      $keylist = [];
      foreach ($klist as $key) {
        $keylist[] = trim($key);
      }
      if ($v['group'] === 'unique') {
        $ret['unique'] = $keylist;
      }
      else {
        $ret['group'][$v['group']] = $keylist;
      }
    }
    return $ret;
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
      ->setDescription(t('The ID of the ZSM Spectra Reporter Plugin entity.'))
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
      ->setDescription(t('Short description of ZSM Spectra Reporter Plugin Instance'))
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


    // Save State?
    $fields['save_data'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Save State?'))
      ->setDescription(t('Whether to save the state of the reporter'))
      ->setDefaultValue(FALSE)
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_buttons',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // State Path
    $fields['save_data_path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Spectra Reporter Save State Filename'))
      ->setDescription(t('Name of the file that will store the current Spectra Reporter State'))
      ->setDefaultValue('spectra_reporter_state.json')
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setRequired(TRUE)
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

    /**
     * Authorization fields
     */
    $fields['auth_location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Spectra Reporting Login URL'))
      ->setDescription(t('The URL of the Spectra Login Endpoint. Usually of the form http://example.com/user/login?_format=json'))
      ->setDefaultValue('')
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setRequired(TRUE)
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

    $fields['auth_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Spectra Reporting Username'))
      ->setDescription(t('The login username.'))
      ->setDefaultValue('')
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setRequired(TRUE)
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

    $fields['auth_pass'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Spectra Reporting Password'))
      ->setDescription(t('The login password.'))
      ->setDefaultValue('')
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setRequired(TRUE)
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

    /**
     * Report fields
     */
    $fields['url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Spectra Reporting POST URL'))
      ->setDescription(t('The URL of the Spectra POST Endpoint. Usually of the form http://example.com/spectra/post'))
      ->setDefaultValue('')
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setRequired(TRUE)
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

    $fields['plugin_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Spectra Reporting Plugin Name'))
      ->setDescription(t('If you want to process the data through a particular plugin, specify its name here.'))
      ->setDefaultValue('')
      ->setSettings(array(
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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['report_alerts'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Post ZSM Alerts?'))
      ->setDescription(t('Post ZSM Alerts?'))
      ->setDefaultValue(array(TRUE))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['report_reports'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Post ZSM Report Data?'))
      ->setDescription(t('Post ZSM Report Data?'))
      ->setDefaultValue(array(TRUE))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    /**
     * Spectra Actor
     */
    $fields['actor_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Actor Type'))
      ->setDescription(t('The type of Spectra Actor Entity, used in the reporting server.'))
      ->setDefaultValue(array(''))
      ->setSettings(array(
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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['actor_property_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Actor Property Type'))
      ->setDescription(t('Either a name or the UUID of a Spectra Actor Entity, for the server to identify.'))
      ->setDefaultValue(array('name'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
        'allowed_values' => ['name' => 'Name', 'uuid' => 'UUID']
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['actor_property'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Actor Name/UUID'))
      ->setDescription(t('The Name or UUID, based on your selection above'))
      ->setDefaultValue(array(''))
      ->setSettings(array(
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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    /**
     * Spectra Object
     */
    $fields['object_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Object Type'))
      ->setDescription(t('The type of Spectra Object Entity, used in the reporting server.'))
      ->setDefaultValue(array(''))
      ->setSettings(array(
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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['object_property_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Object Property Type'))
      ->setDescription(t('Either a name or the UUID of a Spectra Object Entity, for the server to identify.'))
      ->setDefaultValue(array('name'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
        'allowed_values' => ['name' => 'Name', 'uuid' => 'UUID']
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['object_property'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Object Name/UUID'))
      ->setDescription(t('The Name or UUID, based on your selection above'))
      ->setDefaultValue(array(''))
      ->setSettings(array(
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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    /**
     * Spectra Context
     */
    $fields['context_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Context Type'))
      ->setDescription(t('The type of Spectra Context Entity, used in the reporting server.'))
      ->setDefaultValue(array(''))
      ->setSettings(array(
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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['context_property_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Context Property Type'))
      ->setDescription(t('Either a name or the UUID of a Spectra Context Entity, for the server to identify.'))
      ->setDefaultValue(array('name'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
        'allowed_values' => ['name' => 'Name', 'uuid' => 'UUID']
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['context_property'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Context Name/UUID'))
      ->setDescription(t('The Name or UUID, based on your selection above'))
      ->setDefaultValue(array(''))
      ->setSettings(array(
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