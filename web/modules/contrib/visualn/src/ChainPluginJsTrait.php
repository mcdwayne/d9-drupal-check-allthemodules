<?php

namespace Drupal\visualn;

// @todo: check trait name (e.g. rename to VisualNChainPluginJsTrait)
trait ChainPluginJsTrait {

  /**
   * @inheritdoc
   */
  public function jsId() {
    return $this->getPluginId();
  }

  /**
   * Modify plugin configuration before attaching to js settings.
   *
   * Can be used to translate strings etc.
   *
   * @param array $configuration
   */
  public function prepareJsConfig(array &$configuration) {
    // It is also possible to use $this->configuration or $this->getConfiguration()
    // instead of passing $configuration as an argument, though it would become not that
    // intuitive: the idea is that the method takes configuration and clears excesive
    // values (internal or non-public config values etc.) out of it and/or restructures or
    // even processes some of the values before sending to the front-end.
    // If developer decides to send the complete plugin configuration to the
    // front-end, then the method isn't applied to configuration at all,
    // which becomes obvious.
  }

}
