<?php

namespace Drupal\api_tokens;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;

/**
 * Provides an interface for the discovery and instantiation of API token
 * plugins.
 */
interface ApiTokenManagerInterface extends CategorizingPluginManagerInterface {

}
