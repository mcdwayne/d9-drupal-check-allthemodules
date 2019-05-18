<?php
/**
 * @file
 * Contains \Drupal\collect\Plugin\DataType\CollectData.
 */

namespace Drupal\collect\Plugin\DataType;

use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\Exception\ReadOnlyException;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Typed data of a container.
 *
 * This is a complex data object whose properties are defined by a model, which
 * is normally designated at creation through the definition class,
 * \Drupal\collect\TypedData\CollectDataDefinitionInterface.
 *
 * Aside from the model-defined properties, an extra property "_container" is
 * available, providing a Typed Data wrapper for the container entity.
 *
 * @DataType(
 *   id = "collect",
 *   label = @Translation("Collect data"),
 *   definition_class = "\Drupal\collect\TypedData\CollectDataDefinition"
 * )
 */
class CollectData extends TypedData implements \IteratorAggregate, CollectDataInterface {

  /**
   * The value of this Typed Data object.
   *
   * This is an associative array with the following keys:
   *   - data: The data, as parsed by a model plugin.
   *   - container: The container of this data.
   *
   * Parent \Drupal\Component\Plugin\TypedData uses this (locally undefined)
   * field.
   *
   * @var array
   */
  protected $value;

  /**
   * Associative array of loaded data properties, keyed by name.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface[]
   */
  protected $properties;

  /**
   * {@inheritdoc}
   */
  public function get($property_name) {
    // The magic _container property returns the container entity.
    if ($property_name == static::CONTAINER_KEY) {
      return $this->getContainer()->getTypedData();
    }
    if (!isset($this->properties[$property_name])) {
      $data_definition = $this->getDataDefinition();
      $property_definition = $data_definition->getPropertyDefinition($property_name);
      if (empty($property_definition)) {
        throw new \InvalidArgumentException(SafeMarkup::format('Invalid property name @property_name', ['@property_name' => $property_name]));
      }
      $property_value = $this->evaluateQuery($data_definition->getQuery($property_name));
      $this->properties[$property_name] = \Drupal::typedDataManager()->create($property_definition, $property_value, $property_name, $this);
    }
    return $this->properties[$property_name];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluateQuery($query) {
    return $this->getDataDefinition()->getQueryEvaluator()->evaluate($this->getParsedData(), $query);
  }

  /**
   * {@inheritdoc}
   */
  public function getParsedData() {
    return $this->value['data'];
  }

  /**
   * {@inheritdoc}
   */
  public function getContainer() {
    return $this->value['container'];
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value, $notify = TRUE) {
    throw new ReadOnlyException('Container data is read-only.');
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties($include_computed = FALSE) {
    $data_definition = $this->getDataDefinition();
    $names = array_keys($data_definition->getPropertyDefinitions());
    return array_map([$this, 'get'], array_combine($names, $names));
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return array_map(function(TypedDataInterface $property) {
      return $property instanceof ComplexDataInterface ? $property->toArray() : $property->getValue();
    }, $this->getProperties());
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->value === NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($name) {
    if (isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->getProperties());
  }

  /**
   * Gets the Collect data definition.
   *
   * @return \Drupal\collect\TypedData\CollectDataDefinitionInterface
   *   The Collect data definition object.
   */
  public function getDataDefinition() {
    // Override to provide @return typehint.
    return parent::getDataDefinition();
  }

}
