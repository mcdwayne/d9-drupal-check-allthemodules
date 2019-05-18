<?php
/**
 * @file
 * Contains \Drupal\collect\Plugin\collect\Processor\RelationCreatorUri.
 */

namespace Drupal\collect\Plugin\collect\Processor;

use Drupal\collect\Entity\Relation;
use Drupal\collect\Processor\ProcessorBase;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;

/**
 * Creates a relation for each URI property in the data.
 *
 * @Processor(
 *   id = "relation_creator_uri",
 *   label = @Translation("Relation creator"),
 *   description = @Translation("Creates a relation for each URI property in the data")
 * )
 */
class RelationCreatorUri extends ProcessorBase {

  /**
   * The URI of the relations to create.
   *
   * @todo Just make it configurable.
   *
   * @var string
   */
  const RELATION_URI = 'http://purl.org/dc/elements/1.1/relation';

  /**
   * {@inheritdoc}
   */
  public function process(CollectDataInterface $data, array &$context) {
    foreach ($this->collectUriProperties($data) as $property) {
      if ($this->isUriData($property->getDataDefinition())) {

        // Create and save new relation.
        $relation = Relation::create([
          'source_uri' => $data->getContainer()->getOriginUri(),
          'source_id' => $data->getContainer()->id(),
          'target_uri' => $property->getString(),
          'relation_uri' => static::RELATION_URI,
        ]);
        $relation->save();

        // Add to context.
        $context['relations'][$relation->id()] = $relation;
      }
    }
  }

  /**
   * Finds URI-typed properties in the data.
   *
   * @param \Drupal\Core\TypedData\TraversableTypedDataInterface $data
   *   The data object to search for URI properties.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface[]
   *   The URI properties found in the data.
   */
  protected function collectUriProperties(TraversableTypedDataInterface $data) {
    $properties = [];
    foreach ($data as $name => $property) {
      /** @var \Drupal\Core\TypedData\TypedDataInterface $property */
      if ($this->isUriData($property->getDataDefinition())) {
        $properties[] = $property;
      }
      elseif ($property instanceof TraversableTypedDataInterface) {
        $properties = array_merge($properties, $this->collectUriProperties($property));
      }
    }
    return $properties;
  }

  /**
   * Determines whether the given data definition is of type URI.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $data_definition
   *   Data definition to test.
   *
   * @return bool
   *   TRUE if its data class is the Uri plugin or a subclass of it, otherwise
   *   FALSE.
   */
  protected function isUriData(DataDefinitionInterface $data_definition) {
    $uri_class = 'Drupal\Core\TypedData\Plugin\DataType\Uri';
    return $data_definition->getClass() == $uri_class || is_subclass_of($data_definition->getClass(), $uri_class);
  }

}
