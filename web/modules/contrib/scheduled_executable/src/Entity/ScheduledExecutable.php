<?php

namespace Drupal\scheduled_executable\Entity;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Executable\ExecutableInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Scheduled Executable entity.
 *
 * @ContentEntityType(
 *   id = "scheduled_executable",
 *   label = @Translation("Scheduled Executable"),
 *   label_collection = @Translation("Scheduled Executables"),
 *   label_singular = @Translation("Scheduled Executable"),
 *   label_plural = @Translation("Scheduled Executables"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Scheduled Executable",
 *     plural = "@count Scheduled Executables",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\scheduled_executable\Entity\Handler\ScheduledExecutableStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "scheduled_executable",
 *   admin_permission = "administer scheduled executables",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *   },
 * )
 */
class ScheduledExecutable extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // TODO: prevent duplicate entry for combination of entity, time, and key.????????????
  }

  /**
   * Sets the fields for the executable plugin.
   *
   * @param string $plugin_type
   *  The plugin type. This is the name of the plugin manager service for this
   *  plugin, with the initial 'plugin.manager.' removed.
   * @param \Drupal\Core\Executable\ExecutableInterface $plugin_instance
   *  The plugin to set. This must at least implement PluginInspectionInterface
   *  so that we can get its ID. It does not need to implement
   *  ConfigurablePluginInterface.
   *
   * @return
   *  Returns this entity for chaining.
   */
  public function setExecutablePlugin($plugin_type, ExecutableInterface $plugin_instance) {
    // Check the plugin type is valid.
    if (!\Drupal::hasService($this->makePluginManagerServiceName($plugin_type))) {
      throw new \InvalidArgumentException("Invalid plugin type $plugin_type.");
    }

    // Check the plugin is an instance of
    // Drupal\Core\Executable\ExecutableInterface.
    if (!($plugin_instance instanceof ExecutableInterface)) {
      throw new \InvalidArgumentException("Given plugin does not implement Drupal\Core\Executable\ExecutableInterface.");
    }

    // Check the plugin is an instance of
    // Drupal\Core\Executable\PluginInspectionInterface.
    if (!($plugin_instance instanceof PluginInspectionInterface)) {
      throw new \InvalidArgumentException("Given plugin does not implement Drupal\Component\Plugin\PluginInspectionInterface.");
    }

    $this->plugin_type = $plugin_type;
    $this->plugin_id = $plugin_instance->getPluginId();

    // If the plugin uses configuration, set that.
    if ($plugin_instance instanceof ConfigurablePluginInterface) {
      $this->plugin_config->value = $plugin_instance->getConfiguration();
    }

    return $this;
  }

  /**
   * Sets the fields for the target entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $target_entity
   *  The target entity that this will act on.
   *
   * @return
   *  Returns this entity for chaining.
   *
   * @todo this will get replaced with a context system.
   */
  public function setTargetEntity(EntityInterface $target_entity) {
    $this->target_entity_type = $target_entity->getEntityTypeId();
    $this->target_entity_id = $target_entity->Id();

    return $this;
  }

  public function setExecutionTime($timestamp) {
    $this->execution = $timestamp;

    return $this;
  }

  public function setQueuedTime($timestamp) {
    $this->queued = $timestamp;

    return $this;
  }

  public function setResolver($resolver_id) {
    // TODO: consider checking that the resolver ID is a valid plugin ID for
    // DX, as exeptions don't show in cron runs.
    $this->resolver = $resolver_id;

    return $this;
  }

  public function setKey($key) {
    $this->key_name = $key;

    return $this;
  }

  public function setGroup($group) {
    $this->group_name = $group;

    return $this;
  }

  public function getExecutablePluginInstance() {
    $plugin_manager = \Drupal::service($this->makePluginManagerServiceName($this->plugin_type->value));

    $plugin_config = $this->plugin_config->value;

    if (empty($plugin_config)) {
      $plugin_instance = $plugin_manager->createInstance($this->plugin_id->value, []);
    }
    else {
      $plugin_instance = $plugin_manager->createInstance($this->plugin_id->value, $plugin_config);
    }

    return $plugin_instance;
  }

  // TODO: this will get replaced with a context system.
  public function getTargetEntity() {
    $storage = \Drupal::entityTypeManager()->getStorage($this->target_entity_type->value);
    return $storage->load($this->target_entity_id->value);
  }

  public function getKey() {
    return $this->key_name->value;
  }

  public function getGroup() {
    return $this->group_name->value;
  }

  /**
   * Deduce the plugin manager service name for the executable plugin type.
   *
   * @param string $plugin_type
   *   The plugin type name. This is assumed to be the part of the plugin
   *   manager service name that comes after "plugin.manager.".
   *
   * @return string
   *   The service name of the manager for this plugin type.
   */
  protected function makePluginManagerServiceName($plugin_type) {
    return "plugin.manager.{$plugin_type}";
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // The plugin type is the portion of the plugin manager service's name
    // after the initial 'plugin.manager.'.
    $fields['plugin_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plugin type'))
      ->setDescription(t('The type of the executable plugin.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['plugin_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plugin ID'))
      ->setDescription(t('The ID of the executable plugin.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['plugin_config'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Plugin configuration'))
      ->setDescription(t('The configuration array of the executable plugin.'))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    // @todo Replace these two with a single field for context so we can support
    // Rules and other context-aware executable plugins.
    $fields['target_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Target entity type'))
      ->setDescription(t('The type of the entity to execute on.'))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['target_entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plugin ID'))
      ->setDescription(t('The ID of the entity to execute on.'))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['resolver'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Resolver'))
      ->setDescription(t("The ID of a resolver plugin to deal with multiple items of the same group."))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['group_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group'))
      ->setDescription(t("An arbitrary name to identify this item within a group."))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['key_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Key'))
      ->setDescription(t("An arbitrary key to identify this item uniquely for its execution time."))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the item was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the item was last modified.'));

    $fields['execution'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Execution time'))
      ->setDescription(t('The desired execution time.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['queued'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Queued time'))
      ->setDescription(t('The time that the item was queued.'))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    return $fields;
  }

}
