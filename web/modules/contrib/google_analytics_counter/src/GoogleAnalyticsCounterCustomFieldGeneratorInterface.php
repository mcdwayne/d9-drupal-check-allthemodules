<?php

namespace Drupal\google_analytics_counter;


use Drupal\node\NodeTypeInterface;

/**
 * Defines the Google Analytics Counter custom field generator.
 *
 * @package Drupal\google_analytics_counter
 */
interface GoogleAnalyticsCounterCustomFieldGeneratorInterface {

  /**
   * Prepares to add the custom field and saves the configuration.
   *
   * @param $type
   * @param $key
   * @param $value
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function gacPreAddField($type, $key, $value);

  /**
   * Adds the checked the fields.
   *
   * @param \Drupal\node\NodeTypeInterface $type
   *   A node type entity.
   * @param string $label
   *   The formatter label display setting.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\field\Entity\FieldConfig|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function gacAddField(NodeTypeInterface $type, $label = 'Google Analytics Counter');

  /**
   * Prepares to delete the custom field and saves the configuration.
   *
   * @param $type
   * @param $key
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function gacPreDeleteField($type, $key);

  /**
   * Deletes the unchecked field configurations.
   *
   * @param \Drupal\node\NodeTypeInterface $type
   *   A node type entity.
   *
   * @return null|void
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see GoogleAnalyticsCounterConfigureTypesForm
   */
  public function gacDeleteField(NodeTypeInterface $type);

  /**
   * Deletes the field storage configurations.
   *
   * @return null|void
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see GoogleAnalyticsCounterConfigureTypesForm
   */
  public function gacDeleteFieldStorage();

  /**
   * Creates the gac_type_{content_type} configuration on installation or
   * update.
   */
  public function gacChangeConfigToNull();
}