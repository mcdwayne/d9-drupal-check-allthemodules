<?php

namespace Drupal\fac;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for search plugins.
 */
interface SearchInterface extends PluginInspectionInterface {

  /**
   * Return the name of the search plugin.
   *
   * @return string
   *   The name of the plugin.
   */
  public function getName();

  /**
   * Gets the configuration form for the search plugin.
   *
   * @param array $plugin_config
   *   The plugin config array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The configuration form for the search plugin.
   */
  public function getConfigForm(array $plugin_config, FormStateInterface $form_state);

  /**
   * Return the results for the given key.
   *
   * @param \Drupal\fac\FacConfigInterface $fac_config
   *   The Fac Config object.
   * @param string $langcode
   *   The language code.
   * @param string $key
   *   The query string to get results for.
   *
   * @return array
   *   The result entity ids for the given key.
   */
  public function getResults(FacConfigInterface $fac_config, $langcode, $key);

}
