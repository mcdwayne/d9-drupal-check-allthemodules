<?php

namespace Drupal\pluginreference\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PluginReferenceAutocompleteController
 * @package Drupal\pluginreference\Controller
 *
 * Controller class for the pluginreference autocomplete form element.
 */
class PluginReferenceAutocompleteController extends ControllerBase {

  /**
   * The Drupal container.
   *
   * @var ContainerInterface
   */
  protected $container;

  /**
   * Constructs a EntityAutocompleteController object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal container.
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

  /**
   * Autocomplete callback
   *
   * @param Request $request
   *   The request object.
   * @param $target_type
   *   The pluginreference type to filter results.
   *
   * @return JsonResponse
   *   JSON autocomplete response.
   */
  public function autocomplete(Request $request, $target_type) {
    $matches = [];
    if ($this->container->get('current_user')->hasPermission('pluginreference autocomplete view results') &&
      ($input = $request->query->get('q')) && $this->container->has('plugin.manager.' . $target_type)) {
      foreach ($this->container
                 ->get('plugin.manager.' . $target_type)
                 ->getDefinitions() as $plugin_type_id => $plugin_definition) {
        if (isset($plugin_definition['label']) && stripos($plugin_definition['label'], $input) !== FALSE) {
          $matches[] = ['value' => $plugin_definition['label'] . ' (' . $plugin_type_id . ')', 'label' => $plugin_definition['label']];
        }
      }
    }
    return new JsonResponse($matches);
  }

}
