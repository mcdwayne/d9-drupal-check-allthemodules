<?php

namespace Drupal\jsonrpc_core\Plugin\jsonrpc\Method;

use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\jsonrpc\Object\ParameterBag;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * RPC method to rebuild the routes.
 *
 * @JsonRpcMethod(
 *   id = "route_builder.rebuild",
 *   access = {"administer site configuration"},
 *   usage = @Translation("Rebuilds the application's router. Result is TRUE if the rebuild succeeded, FALSE otherwise"),
 * )
 */
class RouteBuilder extends JsonRpcMethodBase {

  /**
   * The route builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * RouteBuilder constructor.
   *
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, RouteBuilderInterface $route_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ParameterBag $params) {
    return $this->routeBuilder->rebuild();
  }

  /**
   * {@inheritdoc}
   */
  public static function outputSchema() {
    return NULL;
  }

}
