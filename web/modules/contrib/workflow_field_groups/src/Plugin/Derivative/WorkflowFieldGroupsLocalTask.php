<?php

namespace Drupal\workflow_field_groups\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entity bundles.
 */
class WorkflowFieldGroupsLocalTask extends DeriverBase implements ContainerDeriverInterface {
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
   * Entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructor.
   */
  public function __construct(RouteProviderInterface $route_provider, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, TranslationInterface $string_translation) {
    $this->routeProvider = $route_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Only concern ourselves with entities that use the field ui.
      if ($entity_type->get('field_ui_base_route')) {

        $top_weight = 6;
        foreach (['view', 'edit'] as $form_operation) {
          if ($entity_type_id == 'workflow_transition') {
            continue;
          }
          $this->derivatives["workflow_field_groups.$entity_type_id.overview.$form_operation"] = [
            'title' => $this->t('Workflow field groups @form_operation', ['@form_operation' => $form_operation]),
            'route_name' => "workflow_field_groups.$entity_type_id.workflow.default.$form_operation",
            'base_route' => $entity_type->get('field_ui_base_route'),
            'weight' => $top_weight++,
          ];

          // The default secondary tab.
          $this->derivatives["workflow_field_groups.$entity_type_id.workflow.default.$form_operation"] = [
            'title' => $this->t('Default'),
            'route_name' => "workflow_field_groups.$entity_type_id.workflow.default.$form_operation",
            'parent_id' => "workflow_field_groups.workflow:workflow_field_groups.$entity_type_id.overview.$form_operation",
            'weight' => -1,
          ];

          // One local task for each form mode.
          $form_modes = $this->entityDisplayRepository->getFormModes($entity_type_id);

          $mode_weight = 0;
          foreach ($form_modes as $form_mode => $form_mode_info) {
            $this->derivatives["workflow_field_groups.$entity_type_id.workflow.$form_mode.$form_operation.sub"] = [
              'title' => $form_mode_info['label'],
              'route_name' => "workflow_field_groups.$entity_type_id.workflow.form_mode.form_operation",
              'route_parameters' => [
                'form_mode_name' => $form_mode,
                'form_operation' => $form_operation,
              ],
              'parent_id' => "workflow_field_groups.workflow:workflow_field_groups.$entity_type_id.overview.$form_operation",
              'weight' => $mode_weight++,
              'cache_tags' => $this->entityTypeManager->getDefinition('entity_form_display')->getListCacheTags(),
            ];
          }
        }
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
