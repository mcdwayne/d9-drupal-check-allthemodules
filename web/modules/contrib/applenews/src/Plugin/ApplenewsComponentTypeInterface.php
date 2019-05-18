<?php

namespace Drupal\applenews\Plugin;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface ApplenewsComponentTypeInterface.
 *
 * @package Drupal\applenews\Plugin
 */
interface ApplenewsComponentTypeInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Returns the label for use on the administration pages.
   *
   * @return string
   *   The administration label.
   */
  public function label();

  /**
   * Returns the plugin's description.
   *
   * @return string
   *   A string describing the plugin. Might contain HTML and should be already
   *   sanitized for output.
   */
  public function getDescription();

  /**
   * Returns the fully-qualified class name of Component.
   *
   * @return string
   *   A string suitable for instantiating an instance of the class.
   */
  public function getComponentClass();

  /**
   * Returns the component type.
   *
   * It is based on the type of content the component is configured to store
   * and display.
   *
   * @return string
   *   A string representing the component type. (text, image, nested, etc.)
   */
  public function getComponentType();

  /**
   * Returns the settings form for the Component type.
   *
   * @param array $form
   *   An array of form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array
   *   The settings form elements.
   */
  public function settingsForm(array $form, FormStateInterface $form_state);

}
