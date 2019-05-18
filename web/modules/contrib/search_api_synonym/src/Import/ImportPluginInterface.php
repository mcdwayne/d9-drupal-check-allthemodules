<?php

namespace Drupal\search_api_synonym\Import;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Provides an interface for search api synonym import plugins.
 *
 * @ingroup plugin_api
 */
interface ImportPluginInterface extends PluginFormInterface, ConfigurablePluginInterface {

  /**
   * Parse the import file.
   *
   * @param \Drupal\file\Entity\File $file
   *   The temporary file object.
   * @param array $settings
   *   Array with plugin settings.
   *
   * @return string
   *   The parsed file content.
   */
  public function parseFile(File $file, array $settings = []);

  /**
   * Plugin configuration form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state array.
   *
   * @return array
   *   Form array.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state);

  /**
   * Validate configuration form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state array.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state);

  /**
   * Get a list of allowed file extensions.
   *
   * @return array
   *   List of allowed extensions.
   */
  public function allowedExtensions();

}
