<?php

namespace Drupal\drd\Update;

use Drupal\Core\Form\FormStateInterface;

/**
 * DRD Update Storage Plugins Manager.
 *
 * Provides an interface for the discovery and instantiation of DRD Update
 * plugins for storage, build and process steps.
 */
interface ManagerStorageInterface extends ManagerInterface {

  /**
   * Construct and return a storage plugin for execution of update.
   *
   * @param array $settings
   *   The plugin settings.
   *
   * @return PluginStorageInterface
   *   The storage plugin.
   *
   * @throws \Exception
   */
  public function executableInstance(array $settings);

  /**
   * A form API container with all the settings for DRD Update plugins.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $settings
   *   The plugin settings.
   */
  public function buildGlobalForm(array &$form, FormStateInterface $form_state, array $settings);

  /**
   * Validate the settings form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validateGlobalForm(array &$form, FormStateInterface $form_state);

  /**
   * Retrieve values from the settings form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The plugin settings.
   */
  public function globalFormValues(array $form, FormStateInterface $form_state);

}
