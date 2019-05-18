<?php

namespace Drupal\field_tools\Plugin\Derivative;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entity bundles.
 */
class FieldToolsLocalTask extends DeriverBase implements ContainerDeriverInterface {
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
   * Creates an FieldToolsLocalTask object.
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
    $this->derivatives = array();

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->get('field_ui_base_route')) {
        // 'Tools' tab.
        $this->derivatives["field_tools_field_ui_tools_$entity_type_id"] = array(
          'route_name' => "field_tools.field_bulk_clone_$entity_type_id",
          'weight' => 4,
          'title' => $this->t('Tools'),
          'base_route' => $entity_type->get('field_ui_base_route'),
        );

        // Secondary tools tabs.
        // 'Clone fields' tab.
        $this->derivatives["field_tools_field_clone_$entity_type_id"] = array(
          'route_name' => "field_tools.field_bulk_clone_$entity_type_id",
          'weight' => 0,
          'title' => $this->t('Clone fields'),
          'base_route' => $entity_type->get('field_ui_base_route'),
          'parent_id' => "field_tools.field_ui_tools:field_tools_field_ui_tools_$entity_type_id",
        );

        // 'Clone displays' tab.
        $this->derivatives["field_tools_displays_clone_$entity_type_id"] = array(
          'route_name' => "field_tools.displays_clone_$entity_type_id",
          'weight' => 5,
          'title' => $this->t('Clone displays'),
          'base_route' => $entity_type->get('field_ui_base_route'),
          'parent_id' => "field_tools.field_ui_tools:field_tools_field_ui_tools_$entity_type_id",
        );

        // 'Copy display settings' tab.
        $this->derivatives["field_tools.displays_settings_copy_$entity_type_id"] = array(
          'route_name' => "field_tools.displays_settings_copy_$entity_type_id",
          'weight' => 5,
          'title' => $this->t('Copy display settings'),
          'base_route' => $entity_type->get('field_ui_base_route'),
          'parent_id' => "field_tools.field_ui_tools:field_tools_field_ui_tools_$entity_type_id",
        );

        // 'Export to base fields' tab.
        $this->derivatives["field_tools.export_to_code_$entity_type_id"] = array(
          'route_name' => "field_tools.export_to_code_$entity_type_id",
          'weight' => 5,
          'title' => $this->t('Export to base fields code'),
          'base_route' => $entity_type->get('field_ui_base_route'),
          'parent_id' => "field_tools.field_ui_tools:field_tools_field_ui_tools_$entity_type_id",
        );

        // Single field clone tab.
        $this->derivatives["field_tools_field_clone_single_$entity_type_id"] = array(
          'route_name' => "entity.field_config.{$entity_type_id}_field_tools_clone_form",
          'title' => $this->t('Clone'),
          'base_route' => "entity.field_config.{$entity_type_id}_field_edit_form",
          'weight' => 10,
        );
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
