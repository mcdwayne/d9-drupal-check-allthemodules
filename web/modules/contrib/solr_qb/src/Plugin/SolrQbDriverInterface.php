<?php
/**
 * @file
 * Contains \Drupal\solr_qb\Plugin\SolrQbDriverInterface.
 */

namespace Drupal\solr_qb\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

interface SolrQbDriverInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface, ConfigurablePluginInterface {

  /**
   * Process Apache Solr query.
   *
   * @param array $values
   *   Builder form values
   *
   * @return mixed
   *   Query result.
   */
  public function query(array $values);

  /**
   * Build plugin configuration form.
   *
   * @param array $form
   *   Form array.
   * @param array $config
   *   Plugin configuration values.
   *
   * @return mixed
   *   Configuration form array.
   */
  public function buildConfigurationForm(array $form, array $config);

  /**
   * Get plugin title.
   *
   * @return string
   *   Plugin title.
   */
  public function getTitle();

  /**
   * Get name of plugin configuration.
   *
   * @return string
   *   Name of plugin configuration.
   */
  public function getConfigName();

}
