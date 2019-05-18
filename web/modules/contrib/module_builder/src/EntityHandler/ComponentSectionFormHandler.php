<?php

namespace Drupal\module_builder\EntityHandler;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides definitions of form sections, paths, titles, and properties used.
 *
 * The 'code_builder' annotation on a component entity type may define any
 * number of sections to split the entity's edit form into. For each section, it
 * defines the name, title strings, and which properties of the component should
 * be shown in that form section. These sections are presented as tabs alongside
 * the primary edit form. A 'misc' section is always added last, which shows any
 * properties not included in the defined sections.
 * The names of the sections correspond to form operations names, and setting
 * a form class for that operation in the 'handlers' annotation will cause that
 * form class to be used for that section. If no form class is given, the
 * default ComponentSectionForm is used.
 */
class ComponentSectionFormHandler {

  /**
   * The form section data.
   *
   * @var array
   */
  protected $formSectionData;

  /**
   * The form section data with the 'name' section removed.
   *
   * @var array
   */
  protected $formSectionDataDynamic;

  /**
   * Constructs a new ComponentSectionFormHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   */
  public function __construct(EntityTypeInterface $entity_type) {
    $code_builder_annotation = $entity_type->get('code_builder');
    $form_properties = $code_builder_annotation['section_forms'];

    if (!isset($form_properties['name'])) {
      throw new \Exception("Entity type {$entity_type_id} defines the code_builder annotation, but doesn't have a name form set.");
    }

    // The 'misc' and 'generate' forms always gets added: the 'misc' shows all
    // properties not set to another section..
    $form_properties['misc'] = [
      "title" => "Edit %label miscellaneous components",
      "op_title" => "Edit miscellaneous components",
      "tab_title" => "Misc",
      // Empty array is needed here for getUsedComponentProperties() to use.
      "properties" => [],
    ];
    $form_properties['generate'] = [
      "title" => "Generate code for %label",
      "op_title" => "Generate code",
      "tab_title" => "Generate code ",
      // Empty array is needed here for getUsedComponentProperties() to use.
      "properties" => [],
    ];

    $this->formSectionData = $form_properties;

    // The 'name' form gets special treatment.
    // TODO: currently hardcoded in the class.
    // TODO: rename this class to a more generic name.
    //$entity_type->setFormClass('name', \Drupal\module_builder\Form\ModuleNameForm::class);
    unset($form_properties['name']);

    $this->formSectionDataDynamic = $form_properties;
  }

  /**
   * Gets the form operations defined by the component sections.
   *
   * @return string[]
   *   An array of form operation names.
   */
  public function getFormOperations() {
    $form_operations = array_keys($this->formSectionDataDynamic);

    return $form_operations;
  }

  /**
   * Gets the entity operations for the list builder.
   *
   * @return array
   *   An array whose keys are the form operation names, and whose values are
   *   labels for the operation links.
   */
  public function getOperations() {
    $op_data = [];

    foreach ($this->formSectionDataDynamic as $form_op => $section) {
      $route_data[$form_op] = $section['op_title'];
    }

    return $route_data;
  }

  /**
   * Gets the route data for the section forms.
   *
   * @return array
   *   An array whose keys are the path components and values are route titles.
   */
  public function getFormTabRoutePaths() {
    $route_data = [];

    foreach ($this->formSectionDataDynamic as $form_op => $section) {
      $route_data[$form_op] = $section['title'];
    }

    return $route_data;
  }

  /**
   * Gets the local tasks data for the section forms.
   *
   * @return array
   *   An array whose keys are the path components and values are task titles.
   */
  public function getFormTabLocalTasksData() {
    $route_data = [];

    foreach ($this->formSectionDataDynamic as $form_op => $section) {
      $route_data[$form_op] = $section['tab_title'];
    }

    return $route_data;
  }

  /**
   * Gets the component properties to show for a section form.
   *
   * @param string $form_op
   *   The form operation name.
   *
   * @return string[]
   *   An array of property names for the component.
   */
  public function getSectionFormComponentProperties($form_op) {
    return $this->formSectionData[$form_op]['properties'];
  }

  /**
   * Gets all the component properties set to show in section forms.
   *
   * @return string[]
   *   An array of property names for the component.
   */
  public function getUsedComponentProperties() {
    $used_component_properties = [];
    foreach ($this->formSectionData as $form_op => $section_data) {
      $used_component_properties = array_merge($used_component_properties, $section_data['properties']);
    }

    return $used_component_properties;
  }

}
