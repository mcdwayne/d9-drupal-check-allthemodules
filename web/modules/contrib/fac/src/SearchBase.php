<?php

namespace Drupal\fac;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SearchBase.
 */
abstract class SearchBase extends PluginBase implements SearchInterface {

  /**
   * Returns the plugin name.
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

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
  public function getConfigForm(array $plugin_config, FormStateInterface $form_state) {
    return [];
  }

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
  public function getResults(FacConfigInterface $fac_config, $langcode, $key) {
    return [];
  }

}
