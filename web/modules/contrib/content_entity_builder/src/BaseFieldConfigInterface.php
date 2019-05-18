<?php

namespace Drupal\content_entity_builder;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\content_entity_builder\ContentTypeInterface;

/**
 * Defines the interface for base field config.
 *
 * @see \Drupal\content_entity_builder\Annotation\BaseFieldConfig
 * @see \Drupal\content_entity_builder\BaseFieldConfigBase
 * @see \Drupal\content_entity_builder\BaseFieldConfigManager
 * @see plugin_api
 */
interface BaseFieldConfigInterface extends PluginInspectionInterface, ConfigurablePluginInterface, ContextAwarePluginInterface {

  /**
   * Applies a base field to the content entity type.
   *
   * @param \Drupal\content_entity_builder\ContentTypeInterface $content_type
   *   An content entity type object.
   *
   * @return bool
   *   TRUE on success.
   *   FALSE if unable to add the base field to the content entity type.
   */
  public function addBaseField(ContentTypeInterface $content_type);

  /**
   * Returns the extension the derivative would have after adding base field.
   *
   * @param string $extension
   *   The base field extension the derivative has before adding.
   *
   * @return string
   *   The base field extension after adding.
   */
  public function getDerivativeExtension($extension);

  /**
   * Sets the field definition label.
   *
   * @param string $label
   *   The label to set.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Sets a human readable description.
   *
   * Descriptions are usually used on user interfaces where the data is edited
   * or displayed.
   *
   * @param string $description
   *   The description for this field.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Returns the tab label.
   *
   * @return string
   *   The tab label.
   */
  public function label();

  /**
   * Returns the weight of the tab.
   *
   * @return int|string
   *   Either the integer weight of the tab, or an empty string.
   */
  public function getWeight();

  /**
   * Sets the weight for this tab.
   *
   * @param int $weight
   *   The weight for this tab.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Sets the field type.
   *
   * @param $field_type
   *   ID of the field type.
   *
   * @return $this
   */
  public function setFieldType($field_type);

  /**
   * Sets field settings.
   *
   * Note that the method does not unset existing settings not specified in the
   * incoming $settings array.
   *
   * For example:
   * @code
   *   // Given these are the default settings.
   *   $field_definition->getSettings() === [
   *     'fruit' => 'apple',
   *     'season' => 'summer',
   *   ];
   *   // Change only the 'fruit' setting.
   *   $field_definition->setSettings(['fruit' => 'banana']);
   *   // The 'season' setting persists unchanged.
   *   $field_definition->getSettings() === [
   *     'fruit' => 'banana',
   *     'season' => 'summer',
   *   ];
   * @endcode
   *
   * For clarity, it is preferred to use setSetting() if not all available
   * settings are supplied.
   *
   * @param array $settings
   *   The array of field settings.
   *
   * @return $this
   */
  public function setSettings(array $settings);

  /**
   * Sets whether the field can be empty.
   *
   * If a field is required, an entity needs to have at least a valid,
   * non-empty item in that field's FieldItemList in order to pass validation.
   *
   * An item is considered empty if its isEmpty() method returns TRUE.
   * Typically, that is if at least one of its required properties is empty.
   *
   * @param bool $required
   *   TRUE if the field is required. FALSE otherwise.
   *
   * @return $this
   *   The current object, for a fluent interface.
   */
  public function setRequired($required);

  /**
   * Sets a default value.
   *
   * Note that if a default value callback is set, it will take precedence over
   * any value set here.
   *
   * @param mixed $value
   *   The default value for the field. This can be either:
   *   - a literal, in which case it will be assigned to the first property of
   *     the first item.
   *   - a numerically indexed array of items, each item being a property/value
   *     array.
   *   - a non-numerically indexed array, in which case the array is assumed to
   *     be a property/value array and used as the first item
   *   - NULL or array() for no default value.
   *
   * @return $this
   */
  public function setDefaultValue($value);

  /**
   * Build base field definition.
   *
   * @return Drupal\Core\Field\BaseFieldDefinition
   *
   */
  public function buildBaseFieldDefinition();

  /**
   * Returns the applied status.
   *
   * @return bool
   *   The field applied status.
   */
  public function isApplied();

  /**
   * Sets the field applied.
   *
   * @param $applied
   *   bool value indicate field applied.
   *
   * @return $this
   */
  public function setApplied($applied);
  
  /**
   * Returns the index status.
   *
   * @return bool
   *   The field index status.
   */
  public function hasIndex();

  /**
   * Sets the field index.
   *
   * @param $index
   *   bool value indicate field index.
   *
   * @return $this
   */
  public function setIndex($index); 

  /**
   * Returns the code for this base field.
   *
   * @return string
   *   The code for this base field.
   */
  public function exportCode();   

}
