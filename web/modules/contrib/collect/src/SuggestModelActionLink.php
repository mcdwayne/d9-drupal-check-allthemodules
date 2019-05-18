<?php

/**
 * @file
 * Contains \Drupal\collect\SuggestModelActionLink.
 */

namespace Drupal\collect;

use Drupal\collect\Model\ModelManagerInterface;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Local action link for suggesting a model for container.
 */
class SuggestModelActionLink extends LocalActionDefault {

  use StringTranslationTrait;

  /**
   * The injected model plugin manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a SuggestModelActionLink.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider, RouteMatchInterface $route_match, ModelManagerInterface $model_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider);
    $this->routeMatch = $route_match;
    $this->modelManager = $model_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider'),
      $container->get('current_route_match'),
      $container->get('plugin.manager.collect.model')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    $container = $this->routeMatch->getParameter('collect_container');
    if ($suggested_model = $this->modelManager->suggestModel($container)) {
      $definition = $this->modelManager->getDefinition($suggested_model->getPluginId(), TRUE);
      return $this->t('Set up a %label model', ['%label' => $definition['label']]);
    }
    return parent::getTitle($request);
  }
}
