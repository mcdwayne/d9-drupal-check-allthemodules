<?php

namespace Drupal\forms_steps\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\forms_steps\Service\RouteHelper;
use Drupal\forms_steps\Step;

/**
 * Defines a class to build a listing of Workflow entities.
 *
 * @see \Drupal\forms_steps\Entity\Workflow
 */
class WorkflowListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['instance_id'] = $this->t('Instance ID');
    $header['entity_type'] = $this->t('Entity Type');
    $header['bundle'] = $this->t('Bundle');
    $header['entity_id'] = $this->t('Entity ID');
    $header['form_mode'] = $this->t('Form Mode');
    $header['forms_steps'] = $this->t('Workflow Name');
    $header['step'] = $this->t('Step');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\forms_steps\Entity\FormsSteps */
    $row['id'] = $entity->id();
    $row['instance_id'] = $entity->instance_id->value;
    $row['entity_type'] = $entity->entity_type->value;
    $row['bundle'] = $entity->bundle->value;
    $row['entity_id'] = $entity->entity_id->value;
    $row['form_mode'] = $entity->form_mode->value;
    $row['forms_steps'] = $entity->forms_steps->value;
    $row['step'] = $entity->step->value;

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $operations = parent::getDefaultOperations($entity);
    /** @var \Drupal\forms_steps\Service\FormsStepsManager $formStepsManager */
    $formStepsManager = \Drupal::service('forms_steps.manager');

    if ($entity->forms_steps->value) {
      /** @var \Drupal\forms_steps\Entity\FormsSteps $formsSteps */
      $formsSteps = $formStepsManager->getFormsStepsById(
        $entity->forms_steps->value
      );

      if ($formsSteps) {
        $operations['display'] = [
          'title' => $this->t('View'),
          'weight' => 20,
          'url' => Url::fromUri("internal:" . RouteHelper::getStepUrl(
            $formsSteps->getStep($entity->step->value),
            $entity->instance_id->value
          )),
        ];
      }
    }

    return $operations;
  }

}
