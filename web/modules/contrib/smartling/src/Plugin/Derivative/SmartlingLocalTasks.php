<?php

/**
 * @file
 * Contains \Drupal\smartling\Plugin\Derivative\SmartlingLocalTasks.
 */

namespace Drupal\smartling\Plugin\Derivative;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all translatable entities.
 */
class SmartlingLocalTasks extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The entity manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates an FieldUiLocalTasks object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(RouteProviderInterface $route_provider, EntityManagerInterface $entity_manager, TranslationInterface $string_translation) {
    $this->routeProvider = $route_provider;
    $this->entityManager = $entity_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      // @todo Rebuild routes when translation changed.
      if ($entity_type->isTranslatable() && $entity_type->getLinkTemplate('canonical')) {
        $this->derivatives["entity.$entity_type_id.smartling"] = [
          'route_name' => "entity.$entity_type_id.smartling",
          'weight' => 30,
          'title' => $this->t('Smartling'),
          'base_route' => "entity.$entity_type_id.canonical",
        ] + $base_plugin_definition;
      }
    }
    return $this->derivatives;
  }

}
