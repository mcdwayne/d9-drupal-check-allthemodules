<?php

namespace Drupal\icons;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the required interface for all icon library plugins.
 *
 * @todo Add detailed documentation here explaining the icon library system's
 *   architecture and the relationships between the various objects, including
 *   brief references to the important components that are not coupled to the
 *   interface.
 *
 * @ingroup icons
 */
interface IconLibraryPluginInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface, CacheableDependencyInterface, DerivativeInspectionInterface {

  /**
   * Returns the user-facing icon library label.
   *
   * @return string
   *   The icon library label.
   */
  public function label();

  /**
   * Returns the user-facing icon library description.
   *
   * @return string
   *   The icon library description.
   */
  public function description();

  /**
   * Builds and returns the renderable array for this icon library plugin.
   *
   * If a icon library should not be rendered because it has no content, then
   * this method must also ensure to return no content: it must then only return
   * an empty array, or an empty array with #cache set (with cacheability
   * metadata indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the icon library.
   *
   * @see \Drupal\icons\IconLibraryViewBuilder
   */
  public function build(array &$element, ConfigEntityInterface $entity, $name);

  /**
   * Sets a particular value in the icon library settings.
   *
   * @param string $key
   *   The key of PluginBase::$configuration to set.
   * @param mixed $value
   *   The value to set for the provided key.
   *
   * @see \Drupal\Component\Plugin\PluginBase::$configuration
   */
  public function setConfigurationValue($key, $value);

  /**
   * Returns configuration form elements specific to this icon library plugin.
   *
   * Icon libraries that need to add form elements to the normal icon library
   * configuration form should implement this method.
   *
   * @param array $form
   *   The form definition array for the icon library configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The renderable form array representing the entire configuration form.
   */
  public function iconLibraryForm(array $form, FormStateInterface $form_state);

  /**
   * Adds icon library type-specific validation for the icon library form.
   *
   * Note that this method takes the form structure and form state for the full
   * icon library configuration form as arguments, not just the elements defined
   * in IconLibraryPluginInterface::iconLibraryForm().
   *
   * @param array $form
   *   The form definition array for the full icon library configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\icons\IconLibraryPluginInterface::iconLibraryForm()
   * @see \Drupal\icons\IconLibraryPluginInterface::iconLibrarySubmit()
   */
  public function iconLibraryValidate(array &$form, FormStateInterface $form_state);

  /**
   * Adds icon library type-specific submission handling for the icon set form.
   *
   * Note that this method takes the form structure and form state for the full
   * icon set configuration form as arguments, not just the elements defined in
   * IconLibraryPluginInterface::iconLibraryForm().
   *
   * @param array $form
   *   The form definition array for the full icon set configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\icons\IconLibraryPluginInterface::iconLibraryForm()
   * @see \Drupal\icons\IconLibraryPluginInterface::iconLibraryValidate()
   */
  public function iconLibrarySubmit(array &$form, FormStateInterface $form_state);

  /**
   * Suggests a machine name to identify an instance of this icon set.
   *
   * The icon library plugin need not verify that the machine name is at all
   * unique. It is only responsible for providing a baseline suggestion; calling
   * code is responsible for ensuring whatever uniqueness is required for the
   * use case.
   *
   * @return string
   *   The suggested machine name.
   */
  public function getMachineNameSuggestion();

  /**
   * Get all icons available in the configuration variable.
   *
   * @return array
   *   Array of icons available from the library plugin instance.
   */
  public function getIcons();

}
