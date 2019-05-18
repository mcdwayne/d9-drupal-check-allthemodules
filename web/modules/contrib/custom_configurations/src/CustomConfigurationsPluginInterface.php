<?php

namespace Drupal\custom_configurations;

use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;

/**
 * Interface CustomConfigurationsPluginInterface.
 *
 * @package Drupal\custom_configurations
 */
interface CustomConfigurationsPluginInterface {

  /**
   * Populates a form with elements to configure the plugin.
   *
   * @param \Drupal\Core\Config\StorableConfigBase $file_config
   *   Config object.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $db_config
   *   The key-value store service.
   * @param string|null $language
   *   Langcode of the locale currently being configured.
   *
   * @return array
   *   Form element.
   */
  public function add(StorableConfigBase $file_config, KeyValueStoreInterface $db_config, $language);

  /**
   * Validate the results.
   *
   * @param \Drupal\Core\Config\StorableConfigBase $file_config
   *   Config object we will be saving our configuration to.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $db_config
   *   The key-value store service.
   * @param array $values
   *   An easier-to-read array of submitted values passed on by the main form.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string|null $language
   *   Langcode of the locale currently being configured.
   */
  public function validate(StorableConfigBase $file_config, KeyValueStoreInterface $db_config, array $values, array &$form, FormStateInterface $form_state, $language);

  /**
   * Submit the results.
   *
   * @param \Drupal\Core\Config\StorableConfigBase $file_config
   *   Config object we will be saving our configuration to.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $db_config
   *   The key-value store service.
   * @param array $values
   *   An easier-to-read array of submitted values passed on by the main form.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string|null $language
   *   Langcode of the locale currently being configured.
   *
   * @return null
   *   Return nothing.
   */
  public function submit(StorableConfigBase $file_config, KeyValueStoreInterface $db_config, array $values, array &$form, FormStateInterface $form_state, $language);

}
