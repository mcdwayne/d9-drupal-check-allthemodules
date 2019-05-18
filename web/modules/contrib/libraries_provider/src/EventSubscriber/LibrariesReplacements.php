<?php

namespace Drupal\libraries_provider\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\hook_event_dispatcher\Event\Theme\LibraryInfoAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\libraries_provider\LibrarySourcePluginManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Perform changes on the existing libraries.
 */
class LibrariesReplacements implements EventSubscriberInterface {

  protected $entityTypeManager;

  protected $sourcePluginManager;

  protected $configFactory;

  /**
   * Constructs a LibrariesReplacements object.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LibrarySourcePluginManager $sourcePluginManager,
    ConfigFactoryInterface $configFactory
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->sourcePluginManager = $sourcePluginManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::LIBRARY_INFO_ALTER => 'replace',
    ];
  }

  /**
   * Respond to the event.
   */
  public function replace(LibraryInfoAlterEvent $event) {
    $libraries = &$event->getLibraries();
    $extension = $event->getExtension();
    foreach ($libraries as $name => &$library) {
      if (!isset($library['libraries_provider'])) {
        continue;
      }
      $library['libraries_provider'] += $this->getLibraryDefaults($extension, $name);
      $library = $this->normalizeLibrary($library);
      $originalSource = $library['libraries_provider']['source'];
      $library = $this->applyConfigurationReplacements($library, $name, $extension);

      $library = $this->replaceByOtherLibraries($library, $name, $extension);

      if ($library['libraries_provider']['enabled']) {
        $sourcePlugin = $this->sourcePluginManager->createInstance($library['libraries_provider']['source']);
        $originalSourcePlugin = $this->sourcePluginManager->createInstance($originalSource);
        foreach (array_keys($library['css'] ?? []) as $component) {
          $library['css'][$component] = $this->replaceComponent(
            $library['css'][$component],
            $library,
            $sourcePlugin,
            $originalSourcePlugin
          );
        }
        $library['js'] = $this->replaceComponent(
          $library['js'] ?? [],
          $library,
          $sourcePlugin,
          $originalSourcePlugin
        );
      }
      else {
        $this->disableLibrary($library);
      }
    }
  }

  /**
   * Define defaults for all properties.
   */
  protected function getLibraryDefaults($extension, $name) {
    return [
      'id' => $extension . '__' . $name,
      'name' => $name,
      'npm_name' => $name,
      'minified' => 'when_aggregating',
      'replaces' => [],
      'variant' => '',
      'variants_available' => [],
      'blacklist_releases' => [],
    ];
  }

  /**
   * Get the structure of the library ready.
   */
  protected function normalizeLibrary($library) {
    $replaces = [];
    foreach ($library['libraries_provider']['replaces'] as $replace) {
      $replaces[$replace] = $replace;
    }
    $library['libraries_provider']['replaces'] = $replaces;
    return $library;
  }

  /**
   * Apply the configured settings.
   */
  protected function applyConfigurationReplacements($library, $name, $extension) {
    $libraryConfiguration = $this->entityTypeManager->getStorage('library')->load($extension . '__' . $name);
    if ($libraryConfiguration) {
      $library['version'] = $libraryConfiguration->get('version');
      $library['libraries_provider']['enabled'] = $libraryConfiguration->isEnabled();
      $library['libraries_provider']['source'] = $libraryConfiguration->get('source');
      $library['libraries_provider']['minified'] = $libraryConfiguration->get('minified');
      $library['libraries_provider']['variant'] = $libraryConfiguration->get('variant');
      $library['libraries_provider']['replaces'] = $libraryConfiguration->get('replaces');
    }
    return $library;
  }

  /**
   * Apply replacements by other libraries.
   *
   * Sometimes skins like bulmaswatch and bootswatch already provide
   * the base library compiled so the base library is replaced.
   */
  protected function replaceByOtherLibraries($library, $name, $extension) {
    $libraryId = $extension . '__' . $name;
    // TODO: Try to use EntityStorageBase::loadByProperties
    // instead of loadMultiple.
    // It seems to by possible for field entities.
    $libraries = $this->entityTypeManager->getStorage('library')->loadMultiple();
    foreach ($libraries as $replacementLibrary) {
      if (
        $replacementLibrary->id() != $libraryId &&
        in_array($libraryId, $replacementLibrary->get('replaces') ?? [], TRUE)
      ) {
        $library['libraries_provider']['enabled'] = FALSE;
        $library['dependencies'][] = str_replace('__', '/', $replacementLibrary->id());
      }
    }
    return $library;
  }

  /**
   * Replace individual components for the corresponding version.
   */
  protected function replaceComponent($components, $library, $sourcePlugin, $originalSourcePlugin) {
    $newComponents = [];
    foreach ($components as $path => $attributes) {
      $canonicalPath = $originalSourcePlugin->getCanonicalPath($path);
      $extension = pathinfo($canonicalPath, PATHINFO_EXTENSION) === 'js' ? 'js' : 'css';
      $library['libraries_provider']['serve_minified'] =
        ($library['libraries_provider']['minified'] === 'always') ||
        ($library['libraries_provider']['minified'] === 'when_aggregating' && $this->configFactory->get('system.performance')->get($extension . 'preprocess'));
      $path = $sourcePlugin->getPath($canonicalPath, $library);
      $attributes['minified'] = $library['libraries_provider']['serve_minified'];
      $attributes['type'] = $sourcePlugin->getPluginId() === 'local' ? 'file' : 'external';
      $newComponents[$path] = $attributes;
    }
    return $newComponents;
  }

  /**
   * Void the assets of a library.
   */
  protected function disableLibrary(array &$library) {
    $library['css'] = ['base' => []];
    $library['js'] = [];
  }

}
