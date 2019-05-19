<?php

namespace Drupal\social_hub\Utils;

/**
 * Define an interface for extension-based resolvers.
 */
interface ExtensionResolverInterface {

  /**
   * Get the extensions.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   An array of extensions keys.
   */
  public function getExtensions();

}
