<?php

namespace Drupal\rng_quick\Plugin\Block;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rng\EventManagerInterface;

/**
 * Base class for RNG Quick blocks.
 */
abstract class RngQuickBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Base constructor for RngQuickBlockBase.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, EventManagerInterface $event_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    try {
      /** @var \Drupal\Core\Entity\EntityInterface $event */
      if ($event = $this->getContextValue('rng_event')) {
        // Do not show if on register forms.
        $entity_type = $event->getEntityTypeId();
        $registration_routes = [
          "rng.event.$entity_type.register",
          "rng.event.$entity_type.register.type_list",
        ];
        if (in_array($this->routeMatch->getRouteName(), $registration_routes)) {
          return AccessResult::neutral()
            ->addCacheContexts(['route']);
        }

        $context = ['event' => $event];
        return $this->entityTypeManager
          ->getAccessControlHandler('registration')
          ->createAccess(NULL, NULL, $context, TRUE)
          ->addCacheableDependency($event)
          ->addCacheContexts(['rng_event', 'user']);
      }
    }
    catch (PluginException $e) {
    }
    return AccessResult::neutral();
  }

}
