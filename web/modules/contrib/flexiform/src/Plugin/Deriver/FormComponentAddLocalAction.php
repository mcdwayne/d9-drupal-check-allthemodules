<?php

namespace Drupal\flexiform\Plugin\Deriver;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flexiform\FormComponentTypePluginManager;
use Drupal\flexiform\FormComponent\FormComponentTypeCreateableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides local action definitions for all entity bundles.
 */
class FormComponentAddLocalAction extends DeriverBase implements ContainerDeriverInterface {
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
   * The form component type plugin manager.
   *
   * @var \Drupal\flexiform\FormComponentTypePluginManager
   */
  protected $formComponentTypeManager;

  /**
   * Constructs a FormComponentAddLocationAction object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\flexiform\FormComponentTypePluginManager $form_component_type_manager
   *   The form component type plugin manager.
   */
  public function __construct(RouteProviderInterface $route_provider, EntityTypeManagerInterface $entity_type_manager, FormComponentTypePluginManager $form_component_type_manager) {
    $this->routeProvider = $route_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->formComponentTypeManager = $form_component_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.flexiform.form_component_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->get('field_ui_base_route')) {
        foreach ($this->formComponentTypeManager->getDefinitions() as $plugin_id => $definition) {
          $component_type = $this->formComponentTypeManager->createInstance($plugin_id);
          if (!($component_type instanceof FormComponentTypeCreateableInterface)) {
            continue;
          }

          $default_options = [
            'title' => $this->t('Add @label', ['@label' => $definition['label']]),
          ];

          $this->derivatives["entity.entity_form_display.{$entity_type_id}.default.component_type.{$plugin_id}.add"] = [
            'route_name' => "entity.entity_form_display.{$entity_type_id}.default.component_type.{$plugin_id}.add",
            'appears_on' => [
              "entity.entity_form_display.{$entity_type_id}.default",
            ],
          ] + $default_options;
          $this->derivatives["entity.entity_form_display.{$entity_type_id}.form_mode.component_type.{$plugin_id}.add"] = [
            'route_name' => "entity.entity_form_display.{$entity_type_id}.form_mode.component_type.{$plugin_id}.add",
            'appears_on' => [
              "entity.entity_form_display.{$entity_type_id}.form_mode",
            ],
          ] + $default_options;
        }
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
