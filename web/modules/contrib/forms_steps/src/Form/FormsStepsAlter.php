<?php

namespace Drupal\forms_steps\Form;

use Drupal\Core\Form\FormState;
use Drupal\forms_steps\Step;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Class FormsStepsAlter.
 *
 * @package Drupal\forms_steps\Form
 */
class FormsStepsAlter {

  /**
   * Handle the form/form_state.
   *
   * @param array $form
   *   Form to handle.
   * @param \Drupal\Core\Form\FormState $form_state
   *   Form State to handle.
   */
  public static function handle(array &$form, FormState $form_state) {
    /** @var \Drupal\forms_steps\Service\FormsStepsManager $formsStepsManager */
    $formsStepsManager = \Drupal::service('forms_steps.manager');

    /** @var \Drupal\forms_steps\Step $step */
    $step = $formsStepsManager->getStepByRoute(\Drupal::routeMatch()
      ->getRouteName());

    // We define the buttons label.
    FormsStepsAlter::setButtonLabel($step, $form);

    // We manage the previous/next buttons.
    FormsStepsAlter::handleNavigation($step, $form);
  }

  /**
   * Define the submit and cancel label using the step configuration.
   *
   * @param \Drupal\forms_steps\Step $step
   *   Current Step.
   * @param array $form
   *   Form to alter.
   */
  public static function setButtonLabel(Step $step, array &$form) {
    if ($step) {
      if ($step->submitLabel()) {
        $form['actions']['submit']['#value'] = t($step->submitLabel());
      }

      if ($step->cancelLabel()) {
        $form['actions']['cancel']['#value'] = t($step->cancelLabel());
      }
    }
  }

  /**
   * Manage previous/next actions.
   *
   * @param \Drupal\forms_steps\Step $step
   *   Current Step.
   * @param array $form
   *   Form to alter.
   */
  public static function handleNavigation(Step $step, array &$form) {
    if ($step) {
      if ($step->displayPrevious()) {
        $form['actions']['previous'] =
        [
          '#type' => 'submit',
          '#value' => t($step->previousLabel()),
          '#name' => 'previous_action',
          '#submit' => [
            ['Drupal\forms_steps\Form\FormsStepsAlter', 'setPreviousRoute'],
          ],
          '#limit_validation_errors' => [['op']],
        ];
      }
    }
  }

  /**
   * Redirect to the next step if one exists.
   *
   * @param array $form
   *   Form to alter.
   * @param \Drupal\Core\Form\FormState $form_state
   *   Form State to update.
   */
  public static function setNextRoute(array &$form, FormState $form_state) {
    /** @var \Drupal\forms_steps\Service\FormsStepsManager $formsStepsManager */
    $route = \Drupal::routeMatch();
    $route_name = $route->getRouteName();
    $formsStepsManager = \Drupal::service('forms_steps.manager');

    /** @var \Drupal\forms_steps\Entity\Workflow $workflowManager */
    $workflowManager = \Drupal::service('forms_steps.workflow.manager');
    $nextRoute = $formsStepsManager->getNextStepRoute($route_name);

    if ($nextRoute) {
      $workflow = $workflowManager->getWorkflowByEntity($form_state->getFormObject()->getEntity());
      $form_state->setRedirect($nextRoute, [
        'instance_id' => $workflow->instance_id->value,
      ]);
    }

    // Set redirection on final step according to redirection policy.
    $forms_steps = \Drupal::service('forms_steps.manager')->getFormsStepsByRoute($route_name);
    $redirection_policy = $forms_steps->getRedirectionPolicy();

    if ($redirection_policy != '') {

      $step = $formsStepsManager->getStepByRoute($route_name);

      if ($step->isLast()) {

        $redirection_target = $forms_steps->getRedirectionTarget();

        switch ($redirection_policy) {
          case 'internal':
            $target_url = \Drupal::service('path.validator')->getUrlIfValid($redirection_target);
            $target_route_name = $target_url->getRouteName();
            $target_route_parameters = $target_url->getrouteParameters();

            $form_state->setRedirect($target_route_name, $target_route_parameters);
            break;

          case 'route':
            $parameters = $route->getParameters()->all();
            $form_state->setRedirect($redirection_target, $parameters);
            break;

          case 'external':
            $form_state->setResponse(new TrustedRedirectResponse($redirection_target, 302));
            break;
        }
      }
    }
  }

  /**
   * Redirect the form to the previous step.
   *
   * @param array $form
   *   Form to alter.
   * @param \Drupal\Core\Form\FormState $form_state
   *   Forms State to Update.
   */
  public static function setPreviousRoute(array &$form, FormState $form_state) {
    /** @var \Drupal\forms_steps\Service\FormsStepsManager $formsStepsManager */
    $route = \Drupal::routeMatch();
    $route_name = $route->getRouteName();

    /** @var \Drupal\forms_steps\Service\FormsStepsManager $formsStepsManager */
    $formsStepsManager = \Drupal::service('forms_steps.manager');

    $previousRoute = $formsStepsManager->getPreviousStepRoute($route_name);

    if ($previousRoute) {
      /** @var \Drupal\forms_steps\Entity\Workflow $workflowManager */
      $workflowManager = \Drupal::service('forms_steps.workflow.manager');

      /** @var \Drupal\Core\Entity\Entity $entity */
      $entity = $form_state->getFormObject()->getEntity();
      if ($entity->isNew()) {
        // For the moment, new entity form doesn't contains the instance_id,
        // hence we need to get it from URL.
        $instanceId = \Drupal::routeMatch()->getParameter('instance_id');
      }
      else {
        $workflow = $workflowManager->getWorkflowByEntity(
          $form_state->getFormObject()->getEntity()
              );
        $instanceId = $workflow->instance_id->value;
      }

      $form_state->setRedirect(
        $previousRoute,
        [
          'instance_id' => $instanceId,
        ]
      );
    }
  }

}
