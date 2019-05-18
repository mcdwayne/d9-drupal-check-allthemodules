<?php

namespace Drupal\jsld\Plugin\jsld;

/**
 * Class JsldPathPluginBase
 */
abstract class JsldPathPluginBase extends JsldPluginBase implements JsldPluginInterface {

  /**
   * Match passed paths.
   */
  const MATCH_TYPE_LISTED = 'listed';

  /**
   * Shows only if path not in match path.
   */
  const MATCH_TYPE_UNLISTED = 'unlisted';

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    if (isset($this->pluginDefinition['enabled'])) {
      return $this->pluginDefinition['enabled'];
    }
    else {
      return TRUE;
    }
  }

}
