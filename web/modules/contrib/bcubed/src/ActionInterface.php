<?php

namespace Drupal\bcubed;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines an interface for action plugins.
 */
interface ActionInterface extends ConfigurablePluginInterface, PluginInspectionInterface {

  /**
   * Return the name of the action.
   *
   * @return string
   *   Action label.
   */
  public function getLabel();

  /**
   * Returns the name of the library which generates this action.
   *
   * @return string
   *   Library, in standard format - eg "modulename/libraryname".
   */
  public function getLibrary();

  /**
   * Settings form.
   *
   * @return array|null
   *   Render array of settings form.
   */
  public function settingsForm(array $form, FormStateInterface $form_state);

  /**
   * Returns bcubed dependencies.
   *
   * @return array|null
   *   BCubed plugin dependencies.
   */
  public function bcubedPluginDependencies();

}
