<?php

namespace Drupal\client_connection\Resolver;

/**
 * Defines the interface for connection resolvers.
 */
interface ConnectionResolverInterface {

  /**
   * Checks whether the resolver applies to the current plugin lookup.
   *
   * @param $plugin_id
   *   The plugin ID to resolve configuration for.
   * @param $contexts
   *   Available contextual information to help resolve the configuration.
   * @param string $channel_id
   *   (optional) The channel ID to resolve configuration for. Useful to help
   *   some resolvers return or not return relevant configuration.
   *
   * @return bool
   *   True of the resolver applies. False otherwise.
   */
  public function applies($plugin_id, array $contexts, $channel_id = 'site');

  /**
   * Resolves the connection configuration.
   *
   * @param string $plugin_id
   *   The plugin ID to resolve configuration for.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   Available contextual information to help resolve the configuration.
   * @param string $channel_id
   *   (optional) The channel ID to resolve configuration for. Useful to help
   *   some resolvers return or not return relevant configuration.
   *
   * @return \Drupal\client_connection\Entity\ClientConnectionConfigInterface|null
   *   The client connection configuration, if resolved. Otherwise NULL,
   *   indicating that the next resolver in the chain should be called.
   */
  public function resolve($plugin_id, array $contexts, $channel_id = 'site');

}
