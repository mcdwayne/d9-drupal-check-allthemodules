<?php

namespace Drupal\forms_steps\Service;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\entity_embed\Exception\EntityNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\forms_steps\Entity\Workflow;

/**
 * Class WorkflowManager.
 *
 * @package Drupal\forms_steps\Service
 */
class WorkflowManager {
  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * FormsStepsManager.
   *
   * @var \Drupal\forms_steps\Service\FormsStepsManager
   */
  private $formsStepsManager;

  /**
   * UUID Service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  private $uuidService;

  /**
   * CurrentRouteMatch.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * WorkflowManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Injected EntityTypeManager instance.
   * @param \Drupal\forms_steps\Service\FormsStepsManager $forms_steps_manager
   *   Injected FormsStepsManager instance.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   Injected UUID instance.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   Injected CurrentRouteMatch instance.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FormsStepsManager $forms_steps_manager,
    UuidInterface $uuid_service,
    CurrentRouteMatch $current_route_match
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->formsStepsManager = $forms_steps_manager;
    $this->uuidService = $uuid_service;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * Returns the first workflow entry of the provided entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity instance to get the workflow from.
   *
   * @return \Drupal\forms_steps\Entity\Workflow|null
   *   Returns the workflow if found, null otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getWorkflowByEntity(EntityInterface $entity) {
    $workflow = NULL;

    // We load all the workflow of that entity type & bundle.
    $workflows = $this->entityTypeManager
      ->getStorage(Workflow::ENTITY_TYPE)
      ->loadByProperties(
        [
          'entity_type' => $entity->getEntityTypeId(),
          'bundle' => $entity->bundle(),
          'entity_id' => $entity->id(),
        ]
      );

    // Only returning the first one.
    if (!empty($workflows)) {
      $workflow = reset($workflows);
    }

    return $workflow;
  }

  /**
   * Workflow info storage on hook_entity_presave().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that is going to be saved.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\entity_embed\Exception\EntityNotFoundException
   */
  public function entityPreSave(EntityInterface $entity) {
    if ($entity->isNew()) {
      return;
    }

    $currentRoute = $this->currentRouteMatch->getRouteName();
    if (
      preg_match('/^forms_steps\./', $currentRoute)
      && strcmp($entity->getEntityTypeId(), Workflow::ENTITY_TYPE) != 0
    ) {

      /** @var \Drupal\forms_steps\Step $step */
      $step = $this->formsStepsManager->getStepByRoute($currentRoute);

      if (is_null($step)) {
        throw new EntityNotFoundException(
          $this->t('Unable to find Forms Steps step entity.')
        );
      }

      // We check if we are currently managing the right entity and not an
      // entity reference.
      if (strcmp($step->entityType(), $entity->getEntityTypeId()) != 0 ||
        strcmp($step->entityBundle(), $entity->bundle()) != 0) {
        return;
      }

      $instanceId = $this->currentRouteMatch->getParameter('instance_id');
      $workflows = [];

      if (!empty($instanceId)) {
        try {
          $workflows = $this->entityTypeManager
            ->getStorage(Workflow::ENTITY_TYPE)
            ->loadByProperties(
              [
                'instance_id' => $instanceId,
                'entity_type' => $entity->getEntityTypeId(),
                'bundle' => $entity->bundle(),
                'step' => $step->id(),
                'entity_id' => $entity->id(),
                'form_mode' => $step->formMode(),
                'forms_steps' => $step->formsSteps()->id(),
              ]
            );
        }
        catch (\Exception $ex) {
          // Nothing to do.
        }
      }
      else {
        $instanceId = $this->uuidService->generate();
      }

      if (empty($workflows)) {
        $workflow = $this->entityTypeManager
          ->getStorage(Workflow::ENTITY_TYPE)
          ->create([
            'instance_id' => $instanceId,
            'entity_type' => $entity->getEntityTypeId(),
            'bundle' => $entity->bundle(),
            'step' => $step->id(),
            'entity_id' => $entity->id(),
            'form_mode' => $step->formMode(),
            'forms_steps' => $step->formsSteps()->id(),
          ]);

        $workflow->save();
      }
    }
  }

  /**
   * Workflow info storage on hook_entity_insert().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that is going to be inserted.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\entity_embed\Exception\EntityNotFoundException
   */
  public function entityInsert(EntityInterface $entity) {
    $currentRoute = $this->currentRouteMatch->getRouteName();
    if (
      preg_match('/^forms_steps\./', $currentRoute)
      && strcmp($entity->getEntityTypeId(), Workflow::ENTITY_TYPE) != 0
    ) {

      /** @var \Drupal\forms_steps\Step $step */
      $step = $this->formsStepsManager->getStepByRoute($currentRoute);

      if (is_null($step)) {
        throw new EntityNotFoundException(
          $this->t('Unable to find Forms Steps step entity.')
        );
      }

      // We check if we are currently managing the right entity and not an
      // entity reference.
      if (strcmp($step->entityType(), $entity->getEntityTypeId()) != 0 ||
        strcmp($step->entityBundle(), $entity->bundle()) != 0) {
        return;
      }

      $instanceId = $this->currentRouteMatch->getParameter('instance_id');
      $workflows = [];

      if (!empty($instanceId)) {
        try {
          $workflows = $this->entityTypeManager
            ->getStorage(Workflow::ENTITY_TYPE)
            ->loadByProperties(
              [
                'instance_id' => $instanceId,
                'entity_type' => $entity->getEntityTypeId(),
                'bundle' => $entity->bundle(),
                'step' => $step->id(),
                'entity_id' => $entity->id(),
                'form_mode' => $step->formMode(),
                'forms_steps' => $step->formsSteps()->id(),
              ]
            );
        }
        catch (\Exception $ex) {
          // Nothing to do.
        }
      }
      else {
        $instanceId = $this->uuidService->generate();
      }

      if (empty($workflows)) {
        $workflow = $this->entityTypeManager
          ->getStorage(Workflow::ENTITY_TYPE)
          ->create([
            'instance_id' => $instanceId,
            'entity_type' => $entity->getEntityTypeId(),
            'bundle' => $entity->bundle(),
            'step' => $step->id(),
            'entity_id' => $entity->id(),
            'form_mode' => $step->formMode(),
            'forms_steps' => $step->formsSteps()->id(),
          ]);

        $workflow->save();
      }
    }
  }
}
