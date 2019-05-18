<?php

namespace Drupal\blizz_bulk_creator\Services;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blizz_bulk_creator\Entity\BulkcreateConfigurationInterface;

/**
 * Interface BulkcreateAdministrationHelperInterface.
 *
 * Defines the API for the administration helper service.
 *
 * @package Drupal\blizz_bulk_creator\Services
 */
interface BulkcreateAdministrationHelperInterface {

  /**
   * Returns all configured bulkcreate configurations.
   *
   * @return array
   *   All configured bulkcreate configuration entities,
   *   keyed by the bulkcreate id.
   */
  public function getBulkcreateConfigurations();

  /**
   * Returns all active bulkcreations.
   *
   * @return \Drupal\blizz_bulk_creator\Entity\BulkcreateUsageInterface[]
   *   All configured bulkcreate usages.
   */
  public function getAllActiveBulkcreations();

  /**
   * Returns all active bulkcreations on a given entity type.
   *
   * @param string $entity_type_id
   *   The entity type id bulkcreations should be determined for.
   *
   * @return \Drupal\blizz_bulk_creator\Entity\BulkcreateUsageInterface[]
   *   The active bulkcreations for this entity type.
   */
  public function getBulkcreationsByEntityType($entity_type_id = NULL);

  /**
   * Returns ready-to-use options carrying configured bulkcreations.
   *
   * @return string[]
   *   The options for the form select field.
   */
  public function getBulkcreateConfigurationOptions();

  /**
   * Returns a single configured bulkcreate usage.
   *
   * @param string $id
   *   The ID of the usage.
   *
   * @return \Drupal\blizz_bulk_creator\Entity\BulkcreateUsageInterface
   *   The bulkcreate usage entity.
   */
  public function getBulkcreateUsage($id);

  /**
   * Get the usages of a specific bulkcreate configuration.
   *
   * @param \Drupal\blizz_bulk_creator\Entity\BulkcreateConfigurationInterface $bulkcreate_configuration
   *   The bulkcreate configuration in question.
   *
   * @return \Drupal\blizz_bulk_creator\Entity\BulkcreateUsageInterface[]
   *   The usages where a specific bulkcreate configuration is in use.
   */
  public function getBulkcreateUsages(BulkcreateConfigurationInterface $bulkcreate_configuration);

  /**
   * Provides the options for the target field.
   *
   * @param \Drupal\blizz_bulk_creator\Entity\BulkcreateConfigurationInterface $bulkcreate_configuration
   *   The bulkcreation to use.
   * @param string $entity_type_id
   *   The entity type id the bulkcreation is to be activated upon.
   * @param string $bundle
   *   The bundle the bulkcreation is to be activated upon.
   *
   * @return string[]
   *   Ready-to-use options for the field select.
   */
  public function getApplicableTargetFields(BulkcreateConfigurationInterface $bulkcreate_configuration, $entity_type_id, $bundle);

  /**
   * Extracts the target field definition from a bulkcreate configuration.
   *
   * @param string $entity_type_id
   *   The entity type id the target field definition is valid for.
   * @param string $bundle
   *   The bundle of the above entity type.
   * @param string $targetFieldDefinition
   *   The target field definition within the given entity type/bundle.
   *
   * @return array
   *   A structured array holding information about the fields of
   *   every stage of the target definition.
   */
  public function getStructuredBulkcreateTargetFieldArray($entity_type_id, $bundle, $targetFieldDefinition);

  /**
   * Checks whether bulkcreations are enabled on a given form.
   *
   * TODO
   * - will be foreseeable obsolete with the development
   *   of a custom non-db field.
   *
   * @param array $form
   *   The form to examine.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The corresponding FormStateInterface to the form given.
   *
   * @return \Drupal\blizz_bulk_creator\Entity\BulkcreateUsageInterface[]
   *   Active bulkcreations on the given form.
   */
  public function getBulkcreateUsagesForForm(array $form, FormStateInterface $form_state);

  /**
   * Returns dynamic access permissions for configured configurations.
   *
   * @return array
   *   The dynamic created permissions.
   */
  public function dynamicPermissions();

  /**
   * Get the correct form widget definition for a given field within a form.
   *
   * @param string $field_name
   *   The name of the field.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition object of the field.
   * @param \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $entityAdapter
   *   The entity adapter carrying the field widget.
   * @param array $form
   *   The form in which the widget should get implemented.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface of the form given.
   * @param string $widget_type
   *   The type of the widget (defaults to the default widget).
   *
   * @return array
   *   The Form API field widget definition.
   */
  public function getFieldWidget($field_name, FieldDefinitionInterface $field_definition, EntityAdapter $entityAdapter, array $form, FormStateInterface $form_state, $widget_type = 'default_widget');

}
