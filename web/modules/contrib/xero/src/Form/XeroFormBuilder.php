<?php

namespace Drupal\xero\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;

/**
 * The Xero form builder service provides methods to create form elements from
 * Xero data types defined in Drupal.
 *
 * Example usage:
 * @code
 * // Get form elements required for a line item.
 * $formBuilder = \Drupal::service('xero.form_builder');
 * $form['lineitems'][] = $formBuilder->getElementFor('xero_line_item');
 *
 * // Get an autocomplete for account.
 * $definition = \Drupal::service('typed_data_manager')->createDefinition('xero_account');
 * $form['Account'] = $formBuilder->getElementForDefinition($definition, 'AccountID');
 * @endcode
 */
class XeroFormBuilder {

  /**
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * Initialize.
   *
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   *   The typed data manager service
   */
  public function __construct(TypedDataManagerInterface $typedDataManager) {
    $this->typedDataManager = $typedDataManager;
  }

  /**
   * Create a render element for the given Xero data type plugin.
   *
   * @param string $plugin_id
   *   The Xero plugin id.
   * @param string $property_name
   *   (Optional) A property name of the data type to return.
   * @return array
   *   A render array.
   * @throws PluginNotFoundException
   */
  public function getElementFor($plugin_id, $property_name = '') {
    $element = [];

    try {
      $definition = $this->typedDataManager->createDataDefinition($plugin_id);

      if (is_subclass_of($definition, '\Drupal\Core\TypedData\ComplexDataDefinitionBase')) {
        if (empty($property_name)) {
          // Get all elements for the definition.
          $element = $this->getElementsForDefinition($definition);
        }
        else {
          // Get the element for the property of the definition.
          $element = $this->getElementForDefinition($definition, $property_name);
        }
      }
      elseif ($definition instanceof ListDataDefinitionInterface) {
        $element = $this->getElementsForListDefinition($definition);
      }
      else {
        // Get the element for the definition.
        $element_type = $this->getElementTypeFromDefinition($definition);
        $element = [
          '#type' => $element_type,
          '#title' => $definition->getLabel(),
          '#description' => $definition->getDescription() ? $definition->getDescription() : '',
        ];
        $element += $this->getAdditionalProperties($element_type, $definition);
      }
    }
    catch (PluginNotFoundException $e) {
      throw $e;
    }

    return $element;
  }

  /**
   * Get a nested form element for a complex data definition with all elements
   * being required properties.
   *
   * @param \Drupal\Core\TypedData\ComplexDataDefinitionBase $parent_definition
   *   The data definition to populate the element for.
   * @return array
   *   A nested form element.
   */
  public function getElementsForDefinition(ComplexDataDefinitionBase $parent_definition) {
    $element = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#title' => $parent_definition->getLabel(),
      '#description' => $parent_definition->getDescription() ? $parent_definition->getDescription() : '',
    ];

    // Each property definition needs to be analysed to find out the best
    // element match.
    foreach ($parent_definition->getPropertyDefinitions() as $name => $definition) {
      // Read-only properties should not be added to the element.
      if (!$definition->isReadOnly()) {
         if (is_subclass_of($definition, '\Drupal\Core\TypedData\ComplexDataDefinitionBase')) {
           // A complex data definition should recurse.
           $element[$name] = $this->getElementsForDefinition($definition);
        }
        elseif ($definition instanceof ListDataDefinitionInterface) {
          // Get the list of elements.
          $element[$name] = $this->getElementsForListDefinition($definition);
        }
        else {
          // Get a single element by its parent definition's property name.
          $element[$name] = $this->getElementForDefinition($parent_definition, $name);
        }
      }
    }

    return $element;
  }

  /**
   * Get the form element for a specific property of a Xero data definition.
   *
   * @param \Drupal\Core\TypedData\ComplexDataDefinitionBase $parent_definition
   *   The Xero type definition.
   * @param string $name
   *   The property name to get the element for.
   * @return array The form element.
   *   The form element.
   *
   * @throws \InvalidArgumentException
   */
  public function getElementForDefinition(ComplexDataDefinitionBase $parent_definition, $name) {
    $element = [];

    $definition = $parent_definition->getPropertyDefinition($name);

    if (!isset($definition)) {
      throw new \InvalidArgumentException('Property not found.');
    }

    if (is_subclass_of($definition, '\Drupal\Core\TypedData\ComplexDataDefinitionBase')) {
      $element = $this->getElementsForDefinition($definition);
    }
    elseif ($definition instanceof ListDataDefinition) {
      $element = $this->getElementsForListDefinition($definition);
    }
    else {
      $element['#type'] = $this->getElementTypeFromDefinition($definition);
      $element['#title'] = $definition->getLabel();
      $element['#description'] = $definition->getDescription() ? $definition->getDescription() : '';

      $element += $this->getAdditionalProperties($element['#type'], $definition, $parent_definition->getDataType());
    }

    return $element;
  }

  /**
   * Get the elements for a list definition.
   *
   * @param \Drupal\Core\TypedData\ListDataDefinitionInterface $list_definition
   *   A list data definition
   * @return array
   *   The nested form element.
   */
  public function getElementsForListDefinition(ListDataDefinitionInterface $list_definition) {
    $element = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#title' => $list_definition->getLabel(),
      '#description' => $list_definition->getDescription() ? $list_definition->getDescription() : '',
    ];

    $definition = $list_definition->getItemDefinition();

    if (is_subclass_of($definition, '\Drupal\Core\TypedData\ComplexDataDefinitionBase')) {
      $element[] = $this->getElementsForDefinition($definition);
    }
    elseif ($definition instanceof ListDataDefinitionInterface) {
      $element[] = $this->getElementsForListDefinition($definition);
    }
    else {
      $element_type = $this->getElementTypeFromDefinition($definition);
      $element[] = [
        '#type' => $element_type,
        '#title' => $definition->getLabel(),
        '#description' => $definition->getDescription() ? $definition->getDescription() : '',
      ];
      $element += $this->getAdditionalProperties($element_type, $definition, $list_definition->getDataType());
    }

    return $element;
  }

  /**
   * Get the element type to use for a definition.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   * @return string
   *   The element machine name to use.
   */
  protected function getElementTypeFromDefinition(DataDefinitionInterface $definition) {
    $type = 'textfield';

    if ($definition->getDataType() === 'boolean') {
      $type = 'checkbox';
    }
    elseif ($definition->getConstraint('XeroChoiceConstraint')) {
      $type = 'select';
    }

    return $type;
  }

  /**
   * Get any additional form properties to merge into the element for a given
   * form element type.
   *
   * @param string $type
   *   The form element type property.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition
   * @param string $parent_type
   *   (Optional) The parent data type.
   * @return array
   *   An array of properties to merge into the form element.
   */
  protected function getAdditionalProperties($type, DataDefinitionInterface $definition, $parent_type = '') {
    $properties = [];

    if ($type === 'select') {
      // Add the Constraint options to the select element.
      $properties['#options'] = array_combine(
        $definition->getConstraint('XeroChoiceConstraint')['choices'],
        $definition->getConstraint('XeroChoiceConstraint')['choices']
      );
    }
    elseif (array_key_exists('XeroGuidConstraint', $definition->getConstraints())) {
      // Add an auto-complete path for data types with guids.
      $properties['#autocomplete_route_name'] = 'xero.autocomplete';
      $properties['#autocomplete_route_parameters'] = ['type' => $parent_type];
    }

    if ($definition->isRequired()) {
      $element['#required'] = TRUE;
    }

    if ($definition->isReadOnly()) {
      $element['#disabled'] = TRUE;
    }

    return $properties;
  }
}