<?php

namespace Drupal\external_entities\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for external entities..
 */
class ExternalEntitiesLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an ExternalEntityTypeLocalTask object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(RouteProviderInterface $route_provider, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->routeProvider = $route_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->getExternalEntityTypes() as $entity_type_id => $entity_type) {
      // External entity type edit tab.
      $this->derivatives[$entity_type_id . '_settings_tab'] = [
        'route_name' => 'entity.external_entity_type.' . $entity_type_id . '.edit_form',
        'title' => $this->t('Edit'),
        'base_route' => 'entity.external_entity_type.' . $entity_type_id . '.edit_form',
      ];

      // External entity type delete tab.
      $this->derivatives[$entity_type_id . '_delete_tab'] = [
        'route_name' => 'entity.external_entity_type.' . $entity_type_id . '.delete_form',
        'title' => $this->t('Delete'),
        'base_route' => 'entity.external_entity_type.' . $entity_type_id . '.edit_form',
        'weight' => 10,
      ];

      // External entity view tab.
      $this->derivatives['entity.' . $entity_type_id . '.canonical'] = [
        'route_name' => 'entity.' . $entity_type_id . '.canonical',
        'title' => $this->t('View'),
        'base_route' => 'entity.' . $entity_type_id . '.canonical',
      ];

      // External entity edit tab.
      if ($entity_type->getDerivedEntityType()->hasLinkTemplate('edit-form')) {
        $this->derivatives['entity.' . $entity_type_id . '.edit_form'] = [
          'route_name' => 'entity.' . $entity_type_id . '.edit_form',
          'title' => $this->t('Edit'),
          'base_route' => 'entity.' . $entity_type_id . '.canonical',
        ];
      }

      // External entity delete tab.
      if ($entity_type->getDerivedEntityType()->hasLinkTemplate('delete-form')) {
        $this->derivatives['entity.' . $entity_type_id . '.delete_form'] = [
          'route_name' => 'entity.' . $entity_type_id . '.delete_form',
          'title' => $this->t('Delete'),
          'base_route' => 'entity.' . $entity_type_id . '.canonical',
        ];
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

  /**
   * Gets all defined external entity types.
   *
   * @return \Drupal\external_entities\ExternalEntityTypeInterface[]
   *   All defined external entity types.
   */
  protected function getExternalEntityTypes() {
    return $this->entityTypeManager
      ->getStorage('external_entity_type')
      ->loadMultiple();
  }

}
