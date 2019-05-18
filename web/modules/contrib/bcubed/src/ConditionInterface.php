<?php

namespace Drupal\bcubed;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines an interface for condition plugins.
 */
interface ConditionInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Return the name of the condition.
   *
   * @return string
   *   name of condition
   */
  public function getLabel();

  /**
   * Returns the name of the library which should be included on the page, or null if none.
   *
   * @return string|null
   *   name of library
   */
  public function getLibrary();

  /**
   * PHP logic to check before page is loaded.
   *
   * @return bool
   *   whether condition has passed or failed
   */
  public function preCondition();

  /**
   * Settings form.
   *
   * @return array|null
   *   settings form render array
   */
  public function settingsForm(array $form, FormStateInterface $form_state);

  /**
   * Returns bcubed dependencies.
   *
   * @return array|null
   *   bcubed plugin dependencies
   */
  public function bcubedPluginDependencies();

}
