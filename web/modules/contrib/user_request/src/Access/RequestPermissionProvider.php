<?php

namespace Drupal\user_request\Access;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\UncacheableEntityPermissionProvider;
use Drupal\user_request\Entity\RequestType;
use Drupal\state_machine\WorkflowManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides permissions for the request entity.
 */
class RequestPermissionProvider extends UncacheableEntityPermissionProvider {

  /**
   * Workflow manager.
   *
   * @var \Drupal\state_machine\WorkflowManagerInterface
   */
  protected $workflowManager;

  /**
   * Constructs a new EntityPermissionProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\state_machine\WorkflowManagerInterface $workflow_manager
   *   Workflow manager.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, WorkflowManagerInterface $workflow_manager) {
    parent::__construct($entity_type_bundle_info);
    $this->workflowManager = $workflow_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.workflow')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityTypePermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildEntityTypePermissions($entity_type);

    // Adds permission to respond request.
    $entity_type_id = $entity_type->id();
    $entity_type_plural_label = $entity_type->getPluralLabel();
    $permissions["respond $entity_type_id"] = [
      'title' => $this->t('Respond to @type', [
        '@type' => $entity_type_plural_label,
      ]),
    ];

    // Adds permissions for specific states.
    if ($workflow_id = $entity_type->getWorkflow()) {
      $workflow = $this->workflowManager->createInstance($workflow_id);
      $states = $workflow->getStates();
      foreach ($states as $state) {
        $permissions["update {$state->getId()} $entity_type_id"] = [
          'title' => $this->t('Update @state @type', [
            '@state' => $state->getLabel(),
            '@type' => $entity_type_plural_label,
          ]),
        ];
        $permissions["update own {$state->getId()} $entity_type_id"] = [
          'title' => $this->t('Update own @state @type', [
            '@state' => $state->getLabel(),
            '@type' => $entity_type_plural_label,
          ]),
        ];
      }
    }

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildBundlePermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildBundlePermissions($entity_type);
    $entity_type_id = $entity_type->id();
    $entity_type_plural_label = $entity_type->getPluralLabel();

    // Adds entity type level permission to view received requests.
    $permissions["view received $entity_type_id"] = [
      'title' => $this->t('View received @type', [
        '@type' => $entity_type_plural_label,
      ]),
    ];    

    // Adds bundle permissions to update received request.
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type->id());
    foreach ($bundles as $bundle_name => $bundle_info) {
      $permissions["view received $bundle_name $entity_type_id"] = [
        'title' => $this->t('@bundle: View received @type', [
          '@bundle' => $bundle_info['label'],
          '@type' => $entity_type_plural_label,
        ]),
      ];
      // "Respond" permission.
      $permissions["respond $bundle_name $entity_type_id"] = [
        'title' => $this->t('@bundle: Respond to @type', [
          '@bundle' => $bundle_info['label'],
          '@type' => $entity_type_plural_label,
        ]),
      ];
    }

    // Adds permissions for specific states.
    foreach ($bundles as $bundle_name => $bundle_info) {
      if ($request_type = RequestType::load($bundle_name)) {
        if ($workflow_id = $request_type->getWorkflow()) {
          $workflow = $this->workflowManager->createInstance($workflow_id);
          $states = $workflow->getStates();
          // Creates permissions for each state.
          foreach ($states as $state) {
            $permissions["update any {$state->getId()} $bundle_name $entity_type_id"] = [
              'title' => $this->t('@bundle: Update any @state @type', [
                '@bundle' => $bundle_info['label'],
                '@state' => strtolower($state->getLabel()),
                '@type' => $entity_type_plural_label,
              ]),
            ];
            $permissions["update own {$state->getId()} $bundle_name $entity_type_id"] = [
              'title' => $this->t('@bundle: Update own @state @type', [
                '@bundle' => $bundle_info['label'],
                '@state' => strtolower($state->getLabel()),
                '@type' => $entity_type_plural_label,
              ]),
            ];
          }
        }
      }
    }

    return $permissions;
  }

}
