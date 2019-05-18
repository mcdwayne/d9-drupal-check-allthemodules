<?php

namespace Drupal\druqs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;

/**
 * Provides route responses for druqs.module.
 */
class DruqsController extends ControllerBase {

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Request.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Constructor.
   *
   * @param Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The injected ConfigFactoryInterface.
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The injected RequestStack.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->requestStack = $request_stack;
    $this->config = $config_factory->get('druqs.configuration');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack')
    );
  }

  /**
   * Callback returning search results as JSON.
   */
  public function search() {

    // Get the search query from POST data.
    $q = $this->requestStack->getCurrentRequest()->request->get('query');

    // Build args for the hook invocation.
    $args = [
      // Strip unnecessary whitespace from the search string.
      'input' => trim($q),
      // Keep track of how many.
      'results_current' => 0,
      // Add maximum amount of results per invocation.
      'results_per_source' => $this->config->get('results_per_source'),
      // Add maximum amount of results per invocation.
      'results_max' => $this->config->get('results_max'),
    ];

    // Invoke hook_druqs_search to allow modules to add their results.
    $output = [];
    if ($results = $this->moduleHandler()->invokeAll('druqs_search', [&$args])) {
      foreach ($results as $result) {
        // Format and escape the actions.
        $actions = [];
        foreach ($result['actions'] as $title => $uri) {
          $actions[Html::escape($title)] = UrlHelper::stripDangerousProtocols($uri);
        }
        // Add formatted and escaped output.
        $output[] = [
          'type' => Html::escape($result['type']),
          'title' => Html::escape($result['title']),
          'actions' => $actions,
        ];
      }
    }

    // Return these results.
    return new JsonResponse($output);
  }

}
