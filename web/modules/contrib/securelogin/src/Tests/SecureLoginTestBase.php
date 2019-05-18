<?php

namespace Drupal\securelogin\Tests;

use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\simpletest\WebTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for Secure login module tests.
 *
 * @group Secure login
 */
abstract class SecureLoginTestBase extends WebTestBase {

  /**
   * Boolean true if the test environment is HTTPS.
   *
   * @var bool
   */
  protected $isSecure;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['securelogin'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->isSecure = Request::createFromGlobals()->isSecure();
  }

  /**
   * Builds a URL for submitting a mock HTTPS request to HTTP test environments.
   */
  protected function httpsUrl($url) {
    return 'core/modules/system/tests/https.php/' . $url;
  }

  /**
   * Builds a URL for submitting a mock HTTP request to HTTPS test environments.
   */
  protected function httpUrl($url) {
    return 'core/modules/system/tests/http.php/' . $url;
  }

  /**
   * Enables modules for this test.
   */
  protected function enableModules(array $modules) {
    // Perform an ExtensionDiscovery scan as this function may receive a
    // profile that is not the current profile, and we don't yet have a cached
    // way to receive inactive profile information.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    $listing = new ExtensionDiscovery(\Drupal::root());
    $module_list = $listing->scan('module');
    // In ModuleHandlerTest we pass in a profile as if it were a module.
    $module_list += $listing->scan('profile');

    // Set the list of modules in the extension handler.
    $module_handler = $this->container->get('module_handler');

    // Write directly to active storage to avoid early instantiation of
    // the event dispatcher which can prevent modules from registering events.
    $active_storage = $this->container->get('config.storage');
    $extension_config = $active_storage->read('core.extension');

    foreach ($modules as $module) {
      if ($module_handler->moduleExists($module)) {
        throw new \LogicException("$module module is already enabled.");
      }
      $module_handler->addModule($module, $module_list[$module]->getPath());
      // Maintain the list of enabled modules in configuration.
      $extension_config['module'][$module] = 0;
    }
    $active_storage->write('core.extension', $extension_config);

    // Update the kernel to make their services available.
    $extensions = $module_handler->getModuleList();
    $this->container->get('kernel')->updateModules($extensions, $extensions);

    // Ensure isLoaded() is TRUE in order to make
    // \Drupal\Core\Theme\ThemeManagerInterface::render() work.
    // Note that the kernel has rebuilt the container; this $module_handler is
    // no longer the $module_handler instance from above.
    $module_handler = $this->container->get('module_handler');
    $module_handler->reload();
    foreach ($modules as $module) {
      if (!$module_handler->moduleExists($module)) {
        throw new \RuntimeException("$module module is not enabled after enabling it.");
      }
    }
  }

  /**
   * Disables modules for this test.
   */
  protected function disableModules(array $modules) {
    // Unset the list of modules in the extension handler.
    $module_handler = $this->container->get('module_handler');
    $module_filenames = $module_handler->getModuleList();
    $extension_config = $this->config('core.extension');
    foreach ($modules as $module) {
      if (!$module_handler->moduleExists($module)) {
        throw new \LogicException("$module module cannot be disabled because it is not enabled.");
      }
      unset($module_filenames[$module]);
      $extension_config->clear('module.' . $module);
    }
    $extension_config->save();
    $module_handler->setModuleList($module_filenames);
    $module_handler->resetImplementations();
    // Update the kernel to remove their services.
    $this->container->get('kernel')->updateModules($module_filenames, $module_filenames);

    // Ensure isLoaded() is TRUE in order to make _theme() work.
    // Note that the kernel has rebuilt the container; this $module_handler is
    // no longer the $module_handler instance from above.
    $module_handler = $this->container->get('module_handler');
    $module_handler->reload();
    foreach ($modules as $module) {
      if ($module_handler->moduleExists($module)) {
        throw new \RuntimeException("$module module is not disabled after disabling it.");
      }
    }
  }

}
