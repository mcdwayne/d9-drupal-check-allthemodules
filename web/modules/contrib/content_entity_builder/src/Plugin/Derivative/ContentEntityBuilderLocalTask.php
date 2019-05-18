<?php

namespace Drupal\content_entity_builder\Plugin\Derivative;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entity bundles.
 */
class ContentEntityBuilderLocalTask extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates an FieldUiLocalTask object.
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
    $content_types = $this->entityManager->getStorage('content_type')->loadMultiple();
    foreach ($content_types as $content_type_id => $content_type) {
      $this->derivatives["admin_form_$content_type_id"] = [
        'route_name' => "entity.$content_type_id.admin_form",
        'weight' => 0,
        'title' => $this->t('Edit'),
        'base_route' => "entity.$content_type_id.admin_form",
      ];
      $this->derivatives["entity.$content_type_id.collection"] = [
        'route_name' => "entity.$content_type_id.collection",
        'weight' => 1,
        'title' => $this->t('List'),
        'base_route' => "entity.$content_type_id.admin_form",
      ];	  

      $this->derivatives["entity.$content_type_id.canonical"] = [
        'route_name' => "entity.$content_type_id.canonical",
        'weight' => 0,
        'title' => $this->t('View'),
        'base_route' => "entity.$content_type_id.canonical",
      ];

      $this->derivatives["entity.$content_type_id.edit_form"] = [
        'route_name' => "entity.$content_type_id.edit_form",
        'weight' => 1,
        'title' => $this->t('Edit'),
        'base_route' => "entity.$content_type_id.canonical",
      ];
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
