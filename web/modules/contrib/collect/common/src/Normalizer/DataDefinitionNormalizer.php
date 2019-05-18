<?php
/**
 * @file
 * Contains \Drupal\collect_common\Normalizer\DataDefinitionNormalizer.
 */

namespace Drupal\collect_common\Normalizer;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalizer for data definition objects.
 */
class DataDefinitionNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * The injected typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * Constructs a new DataDefinitionNormalizer object.
   */
  public function __construct(TypedDataManager $typed_data_manager) {
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string|array
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\TypedData\DataDefinitionInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    if (!$object instanceof DataDefinitionInterface) {
      throw new \InvalidArgumentException('Object is not a data definition.');
    }

    // Recognize field definitions and delegate to the specific normalizer.
    if ($object instanceof FieldDefinitionInterface) {
      return $this->serializer->normalize($object, 'Drupal\Core\Field\FieldDefinitionInterface');
    }

    $normalized = [
      'type' => $object->getDataType(),
      'label' => (string) $object->getLabel(),
      'description' => (string) $object->getDescription(),
    ];

    // For list data types, add the recursively normalized item type.
    if ($object instanceof ListDataDefinitionInterface) {
      $normalized['item_definition'] = $this->serializer->normalize($object->getItemDefinition());
    }

    // For complex data types, recursively normalize each property definition.
    if ($object instanceof ComplexDataDefinitionInterface) {
      foreach ($object->getPropertyDefinitions() as $name => $property_definition) {
        $normalized['properties'][$name] = $this->serializer->normalize($property_definition);
      }
    }

    return $normalized;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    // Recognize field definitions and delegate to the specific normalizer.
    if ($data['type'] == 'field_item') {
      return $this->serializer->denormalize($data, 'Drupal\Core\Field\FieldDefinitionInterface');
    }

    // Use TypedDataManager to create an appropriate data definition. Catch
    // exceptions thrown if the specified data type plugin is not found.
    try {
      $denormalized = $data['type'] == 'list'
        ? ListDataDefinition::create($data['item_definition']['type'])
          ->setItemDefinition($this->serializer->denormalize($data['item_definition'], $class))
        : \Drupal::typedDataManager()->createDataDefinition($data['type']);
    }
    catch (PluginNotFoundException $e) {
      $denormalized = DataDefinition::create('any');
    }

    // In any normal case, the created definition extends from DataDefinition,
    // and thus has these setters.
    if ($denormalized instanceof DataDefinition) {
      $denormalized->setLabel(isset($data['label']) ? $data['label'] : NULL);
      $denormalized->setDescription(isset($data['description']) ? $data['description'] : NULL);

      // Set the child property definitions on complex data definition.
      if ($denormalized instanceof MapDataDefinition) {
        foreach ($data['properties'] as $name => $child_property_definition) {
          $denormalized->setPropertyDefinition($name, $this->serializer->denormalize($child_property_definition, $class));
        }
      }
    }
    return $denormalized;
  }

}
