<?php

namespace Drupal\xero\Normalizer;

use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Drupal\xero\TypedData\Definition\XeroDefinitionInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Implement denormalization for Xero complex data.
 */
class XeroNormalizer extends ComplexDataNormalizer implements DenormalizerInterface {

  /**
   * Typed Data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  protected $supportedInterfaceOrClass = 'Drupal\xero\TypedData\XeroTypeInterface';

  public function __construct(TypedDataManagerInterface $typed_data_manager) {
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    // Get the array map.
    $ret = array();

    /* @var $object \Drupal\Core\TypedData\TypedDataInterface */
    $ret[$object->getName()] = parent::normalize($object, $format, $context);

    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    // The context array requires the Xero data type to be known. If not, then
    // cannot do anything. This is consistent with Entity normalization.
    if (!isset($context['plugin_id']) || empty($context['plugin_id'])) {
      throw new UnexpectedValueException('Plugin id parameter must be included in context.');
    }

    $name = $class::getXeroProperty('xero_name');
    $plural_name = $class::getXeroProperty('plural_name');

    // Wrap the data an array if there is a singular object returned.
    if (isset($data[$plural_name])) {
      if (count(array_filter(array_keys($data[$plural_name][$name]), 'is_string'))) {
        $data[$plural_name][$name] = array($data[$plural_name][$name]);
      }
    }

    $list_definition = $this->typedDataManager->createListDataDefinition($context['plugin_id']);
    /* @var $definition \Drupal\Core\TypedData\ComplexDataDefinitionInterface */
    $definition = $this->typedDataManager->createDataDefinition($context['plugin_id']);
    // Typed Data Manager's createListDataDefinition method is dumb and creates
    // lists of "any" by default so it needs to be set. DrupalWTF.
    $list_definition->setItemDefinition($definition);

    // Create an empty list and then populate each item.
    /* @var $items \Drupal\Core\TypedData\Plugin\DataType\ItemList */
    $items = $this->typedDataManager->create($list_definition, []);
    if (isset($data[$plural_name])) {
      foreach ($data[$plural_name][$name] as $index => $item_data) {
        /* @var $item \Drupal\Core\TypedData\ComplexDataInterface */
        $item = $this->typedDataManager->create($definition, []);

        // Go through each property definition.
        foreach ($definition->getPropertyDefinitions() as $prop => $prop_definition) {
          if (isset($item_data[$prop])) {
            if ($prop_definition instanceof XeroDefinitionInterface) {
              // If the definition is a "xero" type, then recurse directly.
              $prop_data = $this->denormalize(
                $item_data[$prop],
                $prop_definition->getClass(),
                'xml',
                ['plugin_id' => $prop_definition->getDataType()]
              );
              $item->set($prop, $prop_data->getValue(), TRUE);
            }
            elseif ($prop_definition instanceof ListDataDefinitionInterface) {
              // If the definition is a list of xero types, then recurse but pass
              // in the entire data set.
              $prop_data = $this->denormalize(
                $item_data,
                $prop_definition->getItemDefinition()->getClass(),
                'xml',
                ['plugin_id' => $prop_definition->getItemDefinition()->getDataType()]
              );
              $item->set($prop, $prop_data->getValue(), TRUE);
            }
            else {
              // Otherwise set the property directly.
              $item->set($prop, $item_data[$prop], TRUE);
            }
          }
        }
        // Set the value, not the Typed Data object. Might have performance
        // concerns about this.
        $items->offsetSet(NULL, $item->getValue());
      }
    }

    return $items;
  }
}
