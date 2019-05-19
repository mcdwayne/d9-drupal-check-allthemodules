<?php
/**
 * @file
 * Contains \Drupal\zsm\Entity\ZSMCore.
 */

namespace Drupal\zsm\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\zsm\Controller\ZSMController;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the ZSMCore entity.
 *
 * @ingroup zsm
 *
 *
 * @ContentEntityType(
 * id = "zsm_core",
 * label = @Translation("ZSM Core Settings"),
 * handlers = {
 *   "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *   "list_builder" = "Drupal\zsm\Entity\Controller\ZSMCoreListBuilder",
 *   "views_data" = "Drupal\zsm\ZSMCoreViewsData",
 *   "form" = {
 *     "add" = "Drupal\zsm\Form\ZSMCoreForm",
 *     "edit" = "Drupal\zsm\Form\ZSMCoreForm",
 *     "delete" = "Drupal\zsm\Form\ZSMCoreDeleteForm",
 *   },
 *   "access" = "Drupal\zsm\ZSMCoreAccessControlHandler",
 * },
 * list_cache_contexts = { "user" },
 * base_table = "zsm_core",
 * admin_permission = "administer zsm_core entity",
 * fieldable = TRUE,
 * entity_keys = {
 *   "id" = "id",
 *   "uuid" = "uuid",
 *   "uid" = "uid",
 *   "label" = "title",
 * },
 * links = {
 *   "canonical" = "/zsm_core/{zsm_core}",
 *   "edit-form" = "/zsm_core/{zsm_core}/edit",
 *   "delete-form" = "/zsm_core/{zsm_core}/delete",
 *   "collection" = "/zsm_core/list"
 * },
 * field_ui_base_route = "zsm.zsm_core_settings",
 * )
 */
class ZSMCore extends ContentEntityBase {

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     *
     * When a new entity instance is added, set the user_id entity reference to
     * the current user as the creator of the instance.
     */
    public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
        parent::preCreate($storage_controller, $values);
        // Default author to current user.
        $values += array(
            'user_id' => \Drupal::currentUser()->id(),
        );
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
    public function setOwner(UserInterface $account)
    {
        $this->set('user_id', $account->id());
        return $this;
    }

    /**
     * ZSM-specific controller
     */
    public function getZSMController() {
        return ZSMController::create();
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
            ->setDescription(t('The ID of the ZSMCore entity.'))
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
//            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Description
        $fields['description'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Description'))
            ->setDescription(t('Short description of ZSM Instance'))
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
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        /**
         * Verbosity Settings
         */

        $fields['verbosity'] = BaseFieldDefinition::create('list_integer')
            ->setLabel(t('Output/Command Line Verbosity'))
            ->setDescription(t('Determines the verbosity of messages written to command line.'))
            ->setSettings(array(
                'allowed_values' => array(
                    '-1' => '-1: Silent',
                    '0' => '0: Errors Only',
                    '1' => '1: Warnings and startup/finish notices',
                    '2' => '2: Debug text',
                )
            ))
            ->setDefaultValue(-1)
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -4,
            ))
            ->setDisplayOptions('form', array(
                'type' => 'string_textfield',
                'weight' => -4,
            ))
//            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['log_verbosity'] = BaseFieldDefinition::create('list_integer')
            ->setLabel(t('Log Verbosity'))
            ->setDescription(t('Determines the verbosity of messages written to the ZSM log file.'))
            ->setSettings(array(
                'value' => -1,
                'allowed_values' => array(
                    '-1' => '-1: Silent',
                    '0' => '0: Errors Only',
                    '1' => '1: Warnings and startup/finish notices',
                    '2' => '2: Debug text',
                )
            ))
            ->setDefaultValue(-1)
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -4,
            ))
            ->setDisplayOptions('form', array(
                'type' => 'string_textfield',
                'weight' => -4,
            ))
//            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['log_path'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Server Path to log file'))
            ->setDescription(t('Leave blank for default path of /path/to/zsm/user_data/logs/zsm_default.log'))
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
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        /**
         * Save State Settings
         */
        $fields['load_previous_state'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Load Previous State on Initialization?'))
            ->setDescription(t('Enable this if you want to include data from a previous run.'))
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

        $fields['save_on_shutdown'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Save on Shutdown?'))
            ->setDescription(t('Enable this if you want to save your data.'))
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

        $fields['state_filename'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Save State Filename'))
            ->setDescription(t('Filename of the save state JSON file. Defaults to zsm_state.json . Used by the load/save functions.'))
            ->setSettings(array(
                'default_value' => 'zsm_state.json',
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
         * Data Path Settings
         */
        $fields['path_data'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Absolute Path to Data Directory'))
            ->setDescription(t('Used to find the save state file, and all other data files. Modify only if you have a custom location outside of the default user directory.'))
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
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['path_configs'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Absolute Path to the Configs Directory'))
            ->setDescription(t('Locations of the configs directory. Modify only if you have a custom location outside of the default user directory.'))
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
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['path_logs'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Absolute Path to the Logs Directory'))
            ->setDescription(t('Used to find the log files, and to write logs. Modify only if you have a custom location outside of the default user directory.'))
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
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['path_plugins'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Absolute Path to the Custom Plugins Directory'))
            ->setDescription(t('Used for loading custom plugins. Modify only if you have a custom location outside of the default user directory.'))
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
//            ->setRequired(TRUE)
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