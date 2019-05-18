<?php

/**
 * @file
 * Contains \Drupal\powertagging\Entity\PowerTaggingConfig.
 */

namespace Drupal\powertagging\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\semantic_connector\SemanticConnector;

/**
 * Defines the PowerTagging entity.
 *
 * @ConfigEntityType(
 *   id = "powertagging",
 *   label = @Translation("PowerTagging"),
 *   handlers = {
 *     "list_builder" = "Drupal\powertagging\PowerTaggingConfigListBuilder",
 *     "form" = {
 *       "default" =
 *   "Drupal\powertagging\Form\PowerTaggingConfigConnectionForm",
 *       "add" = "Drupal\powertagging\Form\PowerTaggingConfigConnectionForm",
 *       "edit" = "Drupal\powertagging\Form\PowerTaggingConfigConnectionForm",
 *       "edit_config" = "Drupal\powertagging\Form\PowerTaggingConfigForm",
 *       "delete" = "Drupal\powertagging\Form\PowerTaggingConfigDeleteForm",
 *       "clone" = "Drupal\powertagging\Form\PowerTaggingConfigCloneForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\powertagging\PowerTaggingConfigHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "powertagging",
 *   admin_permission = "administer powertagging",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/semantic-drupal/powertagging/add",
 *     "edit-form" =
 *   "/admin/config/semantic-drupal/powertagging/{powertagging}",
 *     "edit-config-form" =
 *   "/admin/config/semantic-drupal/powertagging/{powertagging}/config",
 *     "delete-form" =
 *   "/admin/config/semantic-drupal/powertagging/{powertagging}/delete",
 *     "clone-form" =
 *   "/admin/config/semantic-drupal/powertagging/{powertagging}/clone",
 *     "collection" = "/admin/config/semantic-drupal/powertagging"
 *   },
 *   config_export = {
 *     "title",
 *     "id",
 *     "connection_id",
 *     "project_id",
 *     "config"
 *   }
 * )
 */
class PowerTaggingConfig extends ConfigEntityBase implements PowerTaggingConfigInterface {

  protected $id;

  protected $title;

  protected $connection_id;

  protected $project_id;

  protected $config;

  /** @var \Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection $connection */
  protected $connection;

  /**
   * The constructor of the PowerTagging class.
   *
   * {@inheritdoc|}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    if ($this->isNew()) {
      $this->connection_id = 0;
      $this->config = [];
    }
    else {
      $connection_overrides = \Drupal::config('semantic_connector.settings')
        ->get('override_connections');
      if (isset($connection_overrides[$this->id()])) {
        $overrides = $connection_overrides[$this->id()];
        if (isset($overrides['connection_id'])) {
          $this->connection_id = $overrides['connection_id'];
        }
        if (isset($overrides['project_id'])) {
          $this->project_id = $overrides['project_id'];
        }
        if (isset($overrides['title'])) {
          $this->title = $overrides['title'];
        }
      }
    }

    // TODO: Move default values to a yaml file.
    $default = self::getDefaultConfig();
    $this->config = $this->config + $default;

    $this->connection = SemanticConnector::getConnection('pp_server', $this->connection_id);
  }

  /**
   * Overrides Entity::isNew().
   *
   * Usually an entity is new if no ID exists for it yet. However, entities may
   * be enforced to be new with existing IDs too.
   */
  public function isNew() {
    return !empty($this->enforceIsNew) || !$this->id();
  }

  /**
   * {@inheritdoc|}
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * {@inheritdoc|}
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * {@inheritdoc|}
   */
  public function getConnection() {
    return $this->connection;
  }

  /**
   * {@inheritdoc|}
   */
  public function getConnectionId() {
    return $this->connection_id;
  }

  /**
   * {@inheritdoc|}
   */
  public function setConnectionId($connection_id) {
    $this->connection_id = $connection_id;
    $this->connection = SemanticConnector::getConnection('pp_server', $this->connection_id);
  }

  /**
   * {@inheritdoc|}
   */
  public function getProjectId() {
    return $this->project_id;
  }

  /**
   * {@inheritdoc|}
   */
  public function setProjectId($project_id) {
    $this->project_id = $project_id;
  }

  /**
   * {@inheritdoc|}
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * {@inheritdoc|}
   */
  public function setConfig($config) {
    $this->config = $config + self::getDefaultConfig();
  }

  /**
   * Loads all (filterable) PowerTagging configurations.
   *
   * @param string $connection_id
   *   ID of the Semantic Connector connection.
   * @param bool $only_available_services
   *   Get only PoolParty servers, which currently can be reached.
   *
   * @return array
   *   Return a array with all (or one) configuration set.
   */
  public static function loadConfigs($connection_id = '', $only_available_services = FALSE) {
    $configurations = [];
    $config_query = \Drupal::entityQuery('powertagging');

    if (!empty($connection_id)) {
      $config_query->condition('connection_id', $connection_id);
    }

    $powertagging_config_ids = $config_query->execute();
    $powertagging_configs = static::loadMultiple($powertagging_config_ids);

    /** @var PowerTaggingConfig $powertagging_config */
    foreach ($powertagging_configs as $powertagging_config) {
      if (!$only_available_services || $powertagging_config->getConnection()
          ->available()
      ) {
        $configurations[] = $powertagging_config;
      }
    }

    return $configurations;
  }

  /**
   * Create a new PowerTagging configuration.
   *
   * @param string $title
   *   The title of the configuration.
   * @param string $project_id
   *   The ID of the project
   * @param string $connection_id
   *   The ID of Semantic Connector connection
   * @param array $config
   *   The config of the PowerTagging configuration as an array.
   *
   * @return PowerTaggingConfig
   *   The new PowerTagging configuration.
   */
  public static function createConfig($title, $project_id, $connection_id, array $config = []) {
    $configuration = static::create();
    $configuration->set('id', SemanticConnector::createUniqueEntityMachineName('powertagging', $title));
    $configuration->setTitle($title);
    $configuration->setProjectID($project_id);
    $configuration->setConnectionId($connection_id);
    $configuration->setConfig($config);
    $configuration->save();

    return $configuration;
  }

  /**
   * {@inheritdoc|}
   */
  public function delete() {
    $this->deleteFields();
    parent::delete();
  }

  /**
   * Gets the field settings from the given field.
   *
   * @param array $field
   *   The field array with entity type ID, bundle and field type.
   *
   * @return array
   *   The field settings.
   */
  public function getFieldSettings(array $field) {
    /** @var \Drupal\Core\Entity\EntityFieldManager $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    /** @var \Drupal\field\Entity\FieldConfig $field_definition */
    $field_definition = $entityFieldManager->getFieldDefinitions($field['entity_type_id'], $field['bundle'])[$field['field_type']];

    return $field_definition->getSettings();
  }

  /**
   * Gets a list of all PowerTaggingTags fields from this configuration.
   *
   * @return array
   *   The list of PowerTaggingTags fields.
   */
  public function getFields() {
    /** @var \Drupal\Core\Entity\EntityFieldManager $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fields = $entityFieldManager->getFieldMapByFieldType('powertagging_tags');
    $powertagging_fields = [];
    foreach ($fields as $entity_type_id => $fields_per_entity_type) {
      foreach ($fields_per_entity_type as $field_type => $field) {
        foreach ($field['bundles'] as $bundle) {
          $field_definitions = $entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
          if (isset($field_definitions[$field_type])) {
            /** @var \Drupal\field\Entity\FieldConfig $field_definition */
            $field_definition = $field_definitions[$field_type];
            $powertagging_id = $field_definition->getFieldStorageDefinition()
              ->getSetting('powertagging_id');
            if ($powertagging_id == $this->id()) {
              $powertagging_fields[] = [
                'entity_type_id' => $entity_type_id,
                'bundle' => $bundle,
                'field_type' => $field_type,
                'label' => $field_definition->get('label'),
              ];
            }
          }
        }
      }
    }

    return $powertagging_fields;
  }

  /**
   * Renders a list of PowerTaggingTags fields.
   *
   * @param string $theme
   *   The list type of the return value ("item_list" or "option_list")
   * @param array $fields
   *   The list of PowerTaggingTags fields.
   *
   * @return mixed
   *   A rendered item list or an option list.
   */
  public function renderFields($theme, array $fields) {
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfo $bundleInfo */
    $bundleInfo = \Drupal::service('entity_type.bundle.info');
    /** @var \Drupal\Core\Entity\EntityTypeManager $entityTypeManager */
    $entityTypeManager = \Drupal::service('entity_type.manager');

    $list = NULL;
    switch ($theme) {
      case 'item_list':
        $items = [];
        foreach ($fields as $field) {
          if ($field['entity_type_id'] == 'user') {
            $items[] = t('User (@label)', [
              '@label' => $field['label'],
            ]);          }
          else {
            $type_label = $entityTypeManager->getStorage($field['entity_type_id'])
              ->getEntityType()
              ->getBundleLabel();
            $bundle_labels = $bundleInfo->getBundleInfo($field['entity_type_id']);
            $items[] = t('@entity_type "@bundle" (@label)', [
              '@entity_type' => $type_label,
              '@bundle' => $bundle_labels[$field['bundle']]['label'],
              '@label' => $field['label'],
            ]);
          }
        }
        sort($items);
        $item_list = [
          '#theme' => 'item_list',
          '#items' => $items,
        ];
        $list = \Drupal::service('renderer')->render($item_list);
        break;

      case 'option_list':
        $list = [];
        foreach ($fields as $field) {
          if ($field['entity_type_id'] == 'user') {
            $option_title = t('User (@label)', [
              '@label' => $field['label'],
            ]);
          }
          else {
            $type_label = $entityTypeManager->getStorage($field['entity_type_id'])
              ->getEntityType()
              ->getBundleLabel();
            $bundle_labels = $bundleInfo->getBundleInfo($field['entity_type_id']);
            $option_title = t('@entity_type "@bundle" (@label)', [
              '@entity_type' => $type_label,
              '@bundle' => $bundle_labels[$field['bundle']]['label'],
              '@label' => $field['label'],
            ]);
          }
          $list[$field['entity_type_id'] . '|' . $field['bundle'] . '|' . $field['field_type']] = $option_title;
        }
        asort($list);
        break;
    }

    return $list;
  }

  /**
   * Updates the settings of a field.
   *
   * @param array $field
   *   The field array with entity type ID, bundle and field type.
   * @param string $setting_name
   *   The name of the setting which should be updated.
   * @param mixed $values
   *   The new value.
   */
  public function updateField(array $field, $setting_name, $values) {
    /** @var \Drupal\Core\Entity\EntityFieldManager $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    /** @var \Drupal\field\Entity\FieldConfig $field_definition */
    $field_definition = $entityFieldManager->getFieldDefinitions($field['entity_type_id'], $field['bundle'])[$field['field_type']];
    $field_definition->setSetting($setting_name, $values);
    $field_definition->save();
  }

  /**
   * Deletes all fields
   */
  protected function deleteFields() {
    /** @var \Drupal\Core\Entity\EntityFieldManager $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fields = $this->getFields();
    foreach ($fields as $field) {
      /** @var \Drupal\field\Entity\FieldConfig $field_definition */
      $field_definition = $entityFieldManager->getFieldDefinitions($field['entity_type_id'], $field['bundle'])[$field['field_type']];
      $field_definition->delete();
    }
  }

  /**
   * Returns the default configuration.
   *
   * @return array
   *   The default configuration.
   */
  protected static function getDefaultConfig() {
    return [
      'project' => [
        'languages' => ['en' => 'en'],
        'taxonomy_id' => '',
        'corpus_id' => '',
        'mode' => 'annotation',
      ],
      'limits' => [
        'concepts_per_extraction' => 20,
        'concepts_threshold' => 10,
        'freeterms_per_extraction' => 0,
        'freeterms_threshold' => 50,
      ],
      'concept_scheme_restriction' => [],
      'last_batch_tagging' => time(),
      'data_properties' => [],
    ];
  }

}
