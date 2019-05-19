<?php
/**
 * @file
 */

namespace Drupal\spectra\Controller;

use Drupal\spectra\SpectraPluginManager;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SpectraController
 *
 * Provides the route and API controller for spectra.
 */
class SpectraController extends ControllerBase
{

  protected $spectraPluginManager; //The plugin manager.

  /**
   * Constructor.
   *
   * @param \Drupal\spectra\SpectraPluginManager $plugin_manager
   */

  public function __construct(SpectraPluginManager $plugin_manager) {
    $this->spectraPluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   * This is dependancy injection at work for a controller. Rather than access the global service container via \Drupal::service(), it's best practice to use dependency injection.
   */
  public static function create(ContainerInterface $container) {
    // Use the service container to instantiate a new instance of our controller.
    return new static($container->get('plugin.manager.spectra'));
  }

  /**
   * Accepts DELETE requests and routes to the applicable module for handling
   *
   * @return response;
   *   The settings data to return
   */
  public function delete_api(Request $request) {
    $content = json_decode($request->getContent(), TRUE);
    $plugin = $this->select_spectra_plugin($content);

    // Determine the right plugin
    if ($plugin) {
      $resp = $plugin->handleDeleteRequest($request);

      $response = new JsonResponse($resp);
      return $response;
    }
    else {
      // We failed to find a valid plugin
      $response = new JsonResponse('A valid plugin was not found.');
      return $response;
    }
  }

  /**
   * Accepts GET requests and routes to the applicable module for handling
   *
   * @return response;
   *   The settings data to return
   */
  public function get_api(Request $request) {
    $content = $request->query->all();
    $plugin = $this->select_spectra_plugin($content);

    // Determine the right plugin
    if ($plugin) {
      $resp = $plugin->handleGetRequest($request);

      $response = new JsonResponse($resp);
      return $response;
    }
    else {
      // We failed to find a valid plugin
      $response = new JsonResponse('A valid plugin was not found.');
      return $response;
    }
  }

  /**
   * Accepts POST data and routes to the applicable module for handling
   *
   * @return response;
   *   The settings data to return
   */
  public function post_api(Request $request) {
    $content = json_decode($request->getContent(), TRUE);
    $plugin = $this->select_spectra_plugin($content);

    // Determine the right plugin
    if ($plugin) {
      $resp = $plugin->handlePostRequest($request);

      $response = new JsonResponse($resp);
      return $response;
    }
    else {
      // We failed to find a valid plugin
      $response = new JsonResponse('A valid plugin was not found.');
      return $response;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param content:
   *   the JSON-decoded content, which should have a 'plugin' property
   */
    public function select_spectra_plugin($content) {
      $plugin_definitions = $this->spectraPluginManager->getDefinitions();

      // Get the plugin name
      $plugin_name = 'default_spectra_plugin';
      if (isset($content->plugin) && isset($plugin_definitions[$content->plugin]['class'])) {
        $plugin_name = $content->plugin;
      }
      elseif (is_array($content) && isset($content['plugin']) && isset($plugin_definitions[$content['plugin']]['class'])) {
        $plugin_name = $content['plugin'];
      }

      $p = $plugin_definitions[$plugin_name]['class'];
      $plugin = new $p([], $plugin_definitions[$plugin_name]['id'], $plugin_definitions[$plugin_name]);

      return $plugin;
    }

}