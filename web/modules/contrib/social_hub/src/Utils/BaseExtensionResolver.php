<?php

namespace Drupal\social_hub\Utils;

use Drupal\Core\Asset\LibraryDiscoveryInterface;

/**
 * Base implementation to resolve libraries from extensions.
 */
abstract class BaseExtensionResolver implements ExtensionResolverInterface, LibrariesResolverInterface {

  /**
   * The libraries array (cache).
   *
   * @var array
   */
  protected $libraries;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * Constructs BaseExtensionResolver instance.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery) {
    $this->libraryDiscovery = $library_discovery;
    $this->libraries = [];
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(array $args = NULL) {
    if (empty($this->libraries)) {
      foreach ($this->getExtensions() as $ext => $extension) {
        $libraries = $this->libraryDiscovery->getLibrariesByExtension($ext);

        if (empty($libraries)) {
          continue;
        }

        $this->libraries[$ext] = [
          'name' => \Drupal::service('info_parser')->parse($extension->getPathname())['name'],
          'libraries' => [],
        ];

        foreach ($libraries as $lib => $definition) {
          $this->libraries[$ext]['libraries']["{$ext}/{$lib}"] = $definition;
        }
      }
    }

    usort($this->libraries, function ($a, $b) {
      return strcasecmp($a['name'], $b['name']);
    });

    return $this->libraries;
  }

}
