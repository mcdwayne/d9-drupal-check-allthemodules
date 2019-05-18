<?php
/**
 * @file
 * Contains \Drupal\collect\Entity\Model.
 */

namespace Drupal\collect\Entity;

use Drupal\collect\Processor\ProcessorPluginCollection;
use Drupal\collect\Model\PropertyDefinition;
use Drupal\collect\Model\ModelInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;

/**
 * Model entity.
 *
 * A model specifies what data is contained in a container with corresponding
 * schema URI. The model is primarily used when accessing the container for
 * display or processing. Later, the model will also be consulted when a
 * container is updated.
 * @see Validate containers https://www.drupal.org/node/2449359
 *
 * Some containers are strictly typed by the origin such as CollectJSON with
 * field definition attached.
 * @see FieldDefinition
 * In this case model properties are not only suggested, but strictly defined
 * and can not be altered or deleted. If a definition changes, the related
 * model is automatically updated.
 *
 * Custom properties can always be added in addition.
 *
 * @ConfigEntityType(
 *   id = "collect_model",
 *   label = @Translation("Model"),
 *   admin_permission = "administer collect",
 *   config_prefix = "model",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   handlers = {
 *     "list_builder" = "\Drupal\collect\Model\ModelListBuilder",
 *     "access" = "\Drupal\collect\Model\ModelAccessControlHandler",
 *     "form" = {
 *       "add" = "\Drupal\collect\Form\ModelForm",
 *       "edit" = "\Drupal\collect\Form\ModelForm",
 *       "processing" = "\Drupal\collect\Form\ProcessingForm",
 *       "property" = "\Drupal\collect\Form\ModelPropertyForm",
 *       "delete" = "\Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uri_pattern",
 *     "plugin_id",
 *     "locked",
 *     "container_revision",
 *     "properties",
 *     "processors",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/collect/model",
 *     "add-form" = "/admin/structure/collect/model/add",
 *     "add-suggested-form" = "/admin/structure/collect/model/add/suggested/{collect_container}",
 *     "edit-form" = "/admin/structure/collect/model/manage/{collect_model}",
 *     "processing-form" = "/admin/structure/collect/model/manage/{collect_model}/processing",
 *     "property-edit-form" = "/admin/structure/collect/model/manage/{collect_model}/property/{property_name}",
 *     "property-add-form" = "/admin/structure/collect/model/manage/{collect_model}/property/add",
 *     "property-remove" = "/admin/structure/collect/model/manage/{collect_model}/property/{property_name}/remove",
 *     "delete-form" = "/admin/structure/collect/model/manage/{collect_model}/delete",
 *     "enable" = "/admin/structure/collect/model/manage/{collect_model}/enable",
 *     "disable" = "/admin/structure/collect/model/manage/{collect_model}/disable"
 *   }
 * )
 */
class Model extends ConfigEntityBase implements ModelInterface {

  /**
   * The machine name of this configuration.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of this configuration.
   *
   * @var string
   */
  protected $label;

  /**
   * The pattern that this configuration should match schema URIs against.
   *
   * @var string
   */
  protected $uri_pattern;

  /**
   * The ID of the associated model plugin.
   *
   * @var string
   */
  protected $plugin_id;

  /**
   * Whether the config is protected from deletion.
   *
   * @var bool
   */
  protected $locked = FALSE;

  /**
   * Enable or disable container revisions.
   *
   * @var bool
   */
  protected $container_revision;

  /**
   * The properties defined for this model.
   *
   * @var array
   */
  protected $properties = array();

  /**
   * The configured post-processors to apply to containers of this model.
   *
   * @var array
   */
  protected $processors = array();

  /**
   * The plugin collection for processors.
   *
   * @var \Drupal\collect\Processor\ProcessorPluginCollection
   */
  protected $processorsPluginCollection;

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUriPattern($uri_pattern) {
    $this->uri_pattern = $uri_pattern;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUriPattern() {
    return $this->uri_pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->plugin_id = $plugin_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return $this->locked;
  }

  /**
   * {@inheritdoc}
   */
  public function setContainerRevision($container_revision) {
    $this->container_revision = $container_revision;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainerRevision() {
    return $this->container_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function setProperties(array $property_definitions) {
    $this->properties = $property_definitions;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setProperty($name, $query, array $data_definition) {
    $this->properties[$name] = [
      'query' => $query,
      'data_definition' => $data_definition,
    ];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetProperty($name) {
    unset($this->properties[$name]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties() {
    return $this->properties;
  }

  /**
   * {@inheritdoc}
   */
  public function setTypedProperties(array $property_definitions) {
    foreach ($property_definitions as $name => $property_definition) {
      $this->setTypedProperty($name, $property_definition);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTypedProperty($name, PropertyDefinition $property_definition) {
    return $this->setProperty($name, $property_definition->getQuery(), $this->getSerializer()->normalize($property_definition->getDataDefinition()));
  }

  /**
   * {@inheritdoc}
   */
  public function getTypedProperties() {
    $names = array_keys($this->getProperties());
    return array_map([$this, 'getTypedProperty'], array_combine($names, $names));
  }

  /**
   * {@inheritdoc}
   */
  public function getTypedProperty($name) {
    if (!array_key_exists($name, $this->getProperties())) {
      return NULL;
    }
    $property_definition = $this->getProperties()[$name];
    // Denormalize the data definition.
    $data_definition = $this->getSerializer()->denormalize($property_definition['data_definition'], 'Drupal\Core\TypedData\DataDefinitionInterface');
    return new PropertyDefinition($property_definition['query'], $data_definition);
  }

  /**
   * Returns the serializer from the global container.
   *
   * @return \Symfony\Component\Serializer\Serializer
   *   The serializer service.
   */
  protected function getSerializer() {
    return \Drupal::service('serializer');
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessors(array $processors) {
    $this->processors = $processors;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessors() {
    return $this->processors ?: array();
  }

  /**
   * {@inheritdoc}
   */
  public function removeProcessor($processor_uuid) {
    unset($this->processors[$processor_uuid]);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Depend on the provider of the referenced plugin.
    if (collect_model_manager()->hasDefinition($this->plugin_id)) {
      $this->addDependency('module', collect_model_manager()->getDefinition($this->plugin_id)['provider']);
    }

    // Depend on the providers of used data types.
    foreach ($this->getTypedProperties() as $property_definition) {
      $this->addDataDefinitionDependencies($property_definition->getDataDefinition());
    }

    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'processors' => $this->getProcessorsPluginCollection(),
    ];
  }

  /**
   * Creates a plugin collection object for the processors.
   *
   * @return \Drupal\collect\Processor\ProcessorPluginCollection
   *   The processors plugin collection.
   */
  public function getProcessorsPluginCollection() {
    if (!isset($this->processorsPluginCollection)) {
      $this->processorsPluginCollection = new ProcessorPluginCollection(collect_processor_manager(), $this->getProcessors(), collect_model_manager()->createInstanceFromConfig($this));
    }
    return $this->processorsPluginCollection;
  }

  /**
   * Adds the module dependencies of a data definition.
   *
   * Recurses into properties in the case of a complex definition, and item
   * definitions in the case of lists.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $data_definition
   *   A data definition, denormalized from $this->properties.
   */
  protected function addDataDefinitionDependencies(DataDefinitionInterface $data_definition) {
    /** @var \Drupal\Core\TypedData\TypedDataManager $typed_data_manager */
    $typed_data_manager = \Drupal::service('typed_data_manager');

    if ($data_definition instanceof ListDataDefinitionInterface) {
      $this->addDataDefinitionDependencies($data_definition->getItemDefinition());
    }

    // For field definitions, delegate calculation to the field item class.
    if ($data_definition instanceof FieldDefinitionInterface) {
      /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager */
      $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
      $field_item_class = $field_type_manager->getDefinition($data_definition->getType())['class'];
      // @todo Remove stupid default_value assignment after https://www.drupal.org/node/2479257
      $data_definition->default_value = array();
      $field_dependencies = $field_item_class::calculateDependencies($data_definition);
      $this->dependencies = array_merge_recursive($this->dependencies, $field_dependencies);
    }

    $definition = $typed_data_manager->getDefinition($data_definition->getDataType());
    $this->addDependency('module', $definition['provider']);

    if (isset($data_definition['properties'])) {
      foreach ($data_definition['properties'] as $property) {
        $this->addDataDefinitionDependencies($property);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    // @todo Implement wildcards.
    // Swap arguments to make specific precede generic.
    return strnatcmp($b->getUriPattern(), $a->getUriPattern());
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    // Add suggested properties.
    $this->setTypedProperties(collect_model_manager()->suggestProperties($this));
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    /** @var \Drupal\Core\TypedData\TypedDataManager $typed_data_manager */
    $typed_data_manager = \Drupal::service('typed_data_manager');

    // Remove the property if the module that defines the datatype is
    // uninstalled.
    foreach ($this->getTypedProperties() as $property_name => $property_settings) {
      $data_type_definition = $typed_data_manager->getDefinition($property_settings->getDataDefinition()->getDataType());
      if (in_array($data_type_definition['provider'], $dependencies['module'])) {
        $this->unsetProperty($property_name);
        $changed = TRUE;
      }
    }

    // Remove the processor if the module that defines the processor is
    // uninstalled.
    foreach ($this->getProcessors() as $processor_uuid => $processor_settings) {
      $processor_definition = collect_processor_manager()->getDefinition($processor_settings['plugin_id']);
      if (in_array($processor_definition['provider'], $dependencies['module'])) {
        $this->removeProcessor($processor_uuid);
        $changed = TRUE;
      }
    }

    return $changed;
  }

}
