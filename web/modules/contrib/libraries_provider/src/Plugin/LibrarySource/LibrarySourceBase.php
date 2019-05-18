<?php

namespace Drupal\libraries_provider\Plugin\LibrarySource;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a base plugin for writing library sources.
 */
abstract class LibrarySourceBase extends PluginBase implements LibrarySourceInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  protected $libraryDiscovery;

  protected $availabilityMessages;

  /**
   * Contruct a library source plugin.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    LibraryDiscoveryInterface $libraryDiscovery
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->libraryDiscovery = $libraryDiscovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getLibrary($libraryId) {
    list($extension, $libraryName) = explode('__', $libraryId);
    return $this->libraryDiscovery->getLibraryByName($extension, $libraryName);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailabilityMessage(string $libraryId): string {
    return $this->availabilityMessages[$libraryId] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function applyVariants(string $path, array $library): string {
    if ($library['libraries_provider']['variant']) {
      $path = preg_replace(
        $library['libraries_provider']['variant_regex'],
        '/' . $library['libraries_provider']['variant'],
        $path
      );
    }
    return $path;
  }

}
