<?php


/**
 * @file
 * Contains \Drupal\demo_content\DemoContentExtensionManager.
 */

namespace Drupal\demo_content;

use Drupal\Core\Extension\Extension;
use Drupal\demo_content\DemoContentParserInterface;

/**
 * Class DemoContentExtensionManager
 *
 * @package Drupal\demo_content
 */
class DemoContentExtensionManager implements DemoContentExtensionManagerInterface {

  /**
   * @var \Drupal\demo_content\DemoContentParserInterface
   */
  private $parser;

  /**
   * DemoContentExtensionManager constructor.
   * @param \Drupal\demo_content\DemoContentParserInterface $parser
   */
  public function __construct(DemoContentParserInterface $parser) {
    $this->parser = $parser;
  }

  /**
   * @inheritDoc
   */
  public function getExtension($name) {
    $extensions = $this->getExtensions();
    if (isset($extensions[$name])) {
      return $extensions[$name];
    }

    return false;
  }


  /**
   * @inheritDoc
   */
  public function getExtensions() {
    $cid = 'demo_manager:extensions';

    // Retrieve from cache.
    if ($cache = \Drupal::cache()->get($cid)) {
      return $cache->data;
    }

    $extensions = [];

    // Add modules.
    $extensions += system_rebuild_module_data();

    // Add themes.
    $extensions += \Drupal::service('theme_handler')->rebuildThemeData();

    // Filter demo_content extensions.
    $demo_content_extensions = [];
    foreach ($extensions as $extension_name => $extension) {
      // Continue if extensions is not enabled.
      if (!$extension->status) {
        continue;
      }

      // Continue if extension is not demo_content extension.
      if (!isset($extension->info['demo_content'])) {
        continue;
      }

      // Add demo_content to info.
      $extension->info['demo_content'] = $this->getDemoContent($extension);

      // Add to demo_content_extensions.
      $demo_content_extensions[$extension_name] = $extension;
    }

    // Save to cache.
    \Drupal::cache()->set($cid, $demo_content_extensions);

    return $demo_content_extensions;
  }

  /**
   * Returns demo content for an extension.
   *
   * @param $extension
   *  The extension.
   * @return array
   *  An array of demo content.
   */
  protected function getDemoContent(Extension $extension) {
    $info = [];
    $demo_content_info = $extension->info['demo_content'];

    // Parse the demo content info files.
    if (!empty($demo_content_info)) {
      $extension_path = $extension->getPath();
      foreach ($demo_content_info as $path) {
        $info[$path] = $this->parser->parse($extension_path . '/' . $path, $this->getReplacements($extension));
      }
    }

    return $info;
  }

  /**
   * Returns an array of replacements based on the extension.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *  The extension.
   * @return array
   *  An array of replacements with keys and values.
   */
  protected function getReplacements(Extension $extension) {
    return [
      '$PATH$' => $extension->getPath(),
    ];
  }
}
