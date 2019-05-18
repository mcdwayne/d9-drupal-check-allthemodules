<?php

namespace Drupal\applenews;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface ApplenewsTemplateInterface.
 *
 * @package Drupal\applenews
 */
interface ApplenewsTemplateInterface extends ConfigEntityInterface {

  /**
   * Get the layout values for this template.
   *
   * @return array
   *   An associative array of the Apple News layout values.
   *
   * @see https://developer.apple.com/library/content/documentation/General/Conceptual/Apple_News_Format_Ref/Layout.html#//apple_ref/doc/uid/TP40015408-CH65-SW1
   */
  public function getLayout();

  /**
   * Get the list of components in this template.
   *
   * @return array
   *   An array of Component
   */
  public function getComponents();

  /**
   * Add a new component.
   *
   * @param array $component
   *   An array representing a component config object.
   *
   * @see applenews.schema.yml
   */
  public function addComponent(array $component);

  /**
   * Delete a component from the template.
   *
   * @param string $id
   *   The id corresponding to the correct component in the components array.
   */
  public function deleteComponent($id);

  /**
   * Set the templates components.
   *
   * @param array $components
   *   An array of component arrays, each matching the schema.
   *
   * @see applenews.schema.yml
   */
  public function setComponents(array $components);

  /**
   * Get a specific component from the template.
   *
   * @param string $id
   *   String component id.
   *
   * @return array|null
   *   The array representation of the component, as defined by the schema.
   */
  public function getComponent($id);

}
