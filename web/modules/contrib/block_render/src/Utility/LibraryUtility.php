<?php
/**
 * @file
 * Contains Drupal\block_render\Utility\LibraryUtility.
 */

namespace Drupal\block_render\Utility;

use Drupal\block_render\Library\Library;
use Drupal\block_render\Libraries\Libraries;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Asset\LibraryDependencyResolverInterface;

/**
 * A utility to retrieve necessary libraries.
 */
class LibraryUtility implements LibraryUtilityInterface {

  /**
   * Library Discovery.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * Library Dependency Resolver.
   *
   * @var \Drupal\Core\Asset\LibraryDependencyResolverInterface
   */
  protected $libraryDependencyResolver;

  /**
   * Add the necessary dependencies.
   */
  public function __construct(
    LibraryDiscoveryInterface $library_discovery,
    LibraryDependencyResolverInterface $library_dependency_resolver) {

    $this->libraryDiscovery = $library_discovery;
    $this->libraryDependencyResolver = $library_dependency_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraryResponse(AttachedAssetsInterface $assets) {
    $library_names = $this->getLibrariesToLoad($assets);
    $libraries = new Libraries();
    foreach ($library_names as $library_name) {
      list($extension, $name) = explode('/', $library_name);
      $data = $this->getLibraryDiscovery()->getLibraryByName($extension, $name);
      $version = isset($data['version']) ? $data['version'] : '';
      $libraries->addLibrary(new Library($name, $version));
    }

    return $libraries;
  }

  /**
   * Returns the libraries that need to be loaded.
   *
   * For example, with core/a depending on core/c and core/b on core/d:
   * @code
   * $assets = new AttachedAssets();
   * $assets->setLibraries(['core/a', 'core/b', 'core/c']);
   * $assets->setAlreadyLoadedLibraries(['core/c']);
   * $resolver->getLibrariesToLoad($assets) === ['core/a', 'core/b', 'core/d']
   * @endcode
   *
   * @param \Drupal\Core\Asset\AttachedAssetsInterface $assets
   *   The assets attached to the current response.
   *
   * @return string[]
   *   A list of libraries and their dependencies, in the order they should be
   *   loaded, excluding any libraries that have already been loaded.
   */
  protected function getLibrariesToLoad(AttachedAssetsInterface $assets) {
    return array_diff(
      $this->getLibraryDependencyResolver()->getLibrariesWithDependencies($assets->getLibraries()),
      $this->getLibraryDependencyResolver()->getLibrariesWithDependencies($assets->getAlreadyLoadedLibraries())
    );
  }

  /**
   * Gets the Library Discovery.
   *
   * @return \Drupal\Core\Asset\LibraryDiscoveryInterface
   *   Library Discovery object.
   */
  public function getLibraryDiscovery() {
    return $this->libraryDiscovery;
  }

  /**
   * Gets the Library Dependency Resolver.
   *
   * @return \Drupal\Core\Asset\LibraryDependencyResolverInterface
   *   Library Dependency Resolver object.
   */
  public function getLibraryDependencyResolver() {
    return $this->libraryDependencyResolver;
  }

}
