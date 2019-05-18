<?php

namespace Drupal\forms_steps;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface FormsStepsInterface.
 *
 * @package Drupal\forms_steps
 */
interface FormsStepsInterface extends ConfigEntityInterface {

  /**
   * Adds a step to the forms_steps.
   *
   * @param string $step_id
   *   The step's ID.
   * @param string $label
   *   The step's label.
   * @param string $entityType
   *   The step's entity type.
   * @param string $entityBundle
   *   The step's bundle.
   * @param string $formMode
   *   The step's form_mode.
   * @param string $url
   *   The step's URL.
   *
   * @return \Drupal\forms_steps\FormsStepsInterface
   *   The forms_steps entity.
   */
  public function addStep($step_id, $label, $entityType, $entityBundle, $formMode, $url);

  /**
   * Adds a progress step to the forms_steps.
   *
   * @param string $progress_step_id
   *   The progress step's ID.
   * @param string $label
   *   The progress step's label.
   * @param array $routes
   *   The progress step's active routes.
   * @param string $link
   *   The progress step's link.
   * @param array $link_visibility
   *   The progress step's link visibility.
   *
   * @return \Drupal\forms_steps\FormsStepsInterface
   *   The forms_steps entity.
   */
  public function addProgressStep($progress_step_id, $label, array $routes, $link, array $link_visibility);

  /**
   * Determines if the forms_steps has a step with the provided ID.
   *
   * @param string $step_id
   *   The step's ID.
   *
   * @return bool
   *   TRUE if the forms_steps has a step with the provided ID, FALSE if not.
   */
  public function hasStep($step_id);

  /**
   * Determines if the forms_steps has a progress step with the provided ID.
   *
   * @param string $progress_step_id
   *   The progress step's ID.
   *
   * @return bool
   *   TRUE if the forms_steps has a progress step with the provided ID, FALSE
   *   if not.
   */
  public function hasProgressStep($progress_step_id);

  /**
   * Returns the current step route.
   *
   * @param \Drupal\forms_steps\Step $step
   *   Current Step.
   *
   * @return null|string
   *   Returns the current route.
   */
  public function getStepRoute(Step $step);

  /**
   * Returns the next step route.
   *
   * @param \Drupal\forms_steps\Step $step
   *   Current Step.
   *
   * @return null|string
   *   Returns the next route.
   */
  public function getNextStepRoute(Step $step);

  /**
   * Gets step objects for the provided step IDs.
   *
   * @param string[] $step_ids
   *   A list of step IDs to get. If NULL then all steps will be returned.
   *
   * @return \Drupal\forms_steps\StepInterface[]
   *   An array of forms_steps steps.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $step_ids contains a step ID that does not exist.
   */
  public function getSteps(array $step_ids = NULL);

  /**
   * Gets progress step objects for the provided progress step IDs.
   *
   * @param string[] $progress_step_ids
   *   A list of progress step IDs to get. If NULL then all progress steps will
   *   be returned.
   *
   * @return \Drupal\forms_steps\ProgressStepInterface[]
   *   An array of forms_steps progress steps.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $progress_step_ids contains a progress step ID that does not
   *   exist.
   */
  public function getProgressSteps(array $progress_step_ids = NULL);

  /**
   * Retrieve the last step defined on a forms steps entity.
   *
   * @param string $steps
   *   The forms_steps steps' IDs.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps step.
   */
  public function getLastStep($steps = NULL);

  /**
   * Retrieve the first step defined on a forms steps entity.
   *
   * @param string $steps
   *   The forms_steps steps' IDs.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps step.
   */
  public function getFirstStep($steps = NULL);

  /**
   * Gets a forms_steps step.
   *
   * @param string $step_id
   *   The step's ID.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps step.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $step_id does not exist.
   */
  public function getStep($step_id);

  /**
   * Gets a forms_steps progress step.
   *
   * @param string $progress_step_id
   *   The progress step's ID.
   *
   * @return \Drupal\forms_steps\ProgressStepInterface
   *   The forms_steps progress step.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $progress_step_id does not exist.
   */
  public function getProgressStep($progress_step_id);

  /**
   * Sets a step's label.
   *
   * @param string $step_id
   *   The step ID to set the label for.
   * @param string $label
   *   The step's label.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps entity.
   */
  public function setStepLabel($step_id, $label);

  /**
   * Sets a progress step's label.
   *
   * @param string $progress_step_id
   *   The progress step ID to set the label for.
   * @param string $label
   *   The progress step's label.
   *
   * @return \Drupal\forms_steps\ProgressStepInterface
   *   The forms_steps entity.
   */
  public function setProgressStepLabel($progress_step_id, $label);

  /**
   * Sets a step's weight value.
   *
   * @param string $step_id
   *   The step ID to set the weight for.
   * @param int $weight
   *   The step's weight.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps entity.
   */
  public function setStepWeight($step_id, $weight);

  /**
   * Sets a step's Entity bundle.
   *
   * @param string $step_id
   *   The step ID to set the entity_bundle for.
   * @param int $entityBundle
   *   The step's entity bundle.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps entity.
   */
  public function setStepEntityBundle($step_id, $entityBundle);

  /**
   * Sets a step's Entity type.
   *
   * @param string $step_id
   *   The step ID to set the entity_bundle for.
   * @param int $entity_type
   *   The step's entity type.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps entity.
   */
  public function setStepEntityType($step_id, $entity_type);

  /**
   * Sets a step's form mode value.
   *
   * @param string $step_id
   *   The step ID to set the form mode for.
   * @param int $formMode
   *   The step's form mode.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps entity.
   */
  public function setStepFormMode($step_id, $formMode);

  /**
   * Sets a step's URL value.
   *
   * @param string $step_id
   *   The step ID to set the URL for.
   * @param int $url
   *   The step's URL.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps entity.
   */
  public function setStepUrl($step_id, $url);

  /**
   * Sets a step's submit label.
   *
   * @param string $step_id
   *   The step ID to set the submit label for.
   * @param string $label
   *   The step's submit label.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps entity.
   */
  public function setStepSubmitLabel($step_id, $label);

  /**
   * Sets a step's cancel label.
   *
   * @param string $step_id
   *   The step ID to set the cancel label for.
   * @param string $label
   *   The step's cancel label.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps entity.
   */
  public function setStepCancelLabel($step_id, $label);

  /**
   * Sets a step's cancel route.
   *
   * @param string $step_id
   *   The step ID to set the route for.
   * @param string $route
   *   The step's cancel route.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps entity.
   */
  public function setStepCancelRoute($step_id, $route);

  /**
   * Sets a step's cancel step.
   *
   * @param string $step_id
   *   The step ID to set the cancel step for.
   * @param \Drupal\forms_steps\Step $step
   *   The step's cancel step.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps entity.
   */
  public function setStepCancelStep($step_id, Step $step);

  /**
   * Sets a step's cancel step mode.
   *
   * @param string $step_id
   *   The step ID to set the cancel step mode for.
   * @param string $mode
   *   The step's cancel step mode.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps entity.
   */
  public function setStepCancelStepMode($step_id, $mode);

  /**
   * Sets the progress step's active routes.
   *
   * @param string $progress_step_id
   *   The progress step ID to set the active routes for.
   * @param array $routes
   *   The progress step's active routes.
   *
   * @return \Drupal\forms_steps\ProgressStepInterface
   *   The forms_steps entity.
   */
  public function setProgressStepActiveRoutes($progress_step_id, array $routes);

  /**
   * Sets a progress step's link.
   *
   * @param string $progress_step_id
   *   The progress step ID to set the link for.
   * @param string $link
   *   The progress step's link.
   *
   * @return \Drupal\forms_steps\ProgressStepInterface
   *   The forms_steps entity.
   */
  public function setProgressStepLink($progress_step_id, $link);

  /**
   * Sets a progress step's link visibility.
   *
   * @param string $progress_step_id
   *   The progress step ID to set the link for.
   * @param array $steps
   *   The progress step's link visibility.
   *
   * @return \Drupal\forms_steps\ProgressStepInterface
   *   The forms_steps entity.
   */
  public function setProgressStepLinkVisibility($progress_step_id, array $steps);

  /**
   * Deletes a step from the forms_steps.
   *
   * @param string $step_id
   *   The step ID to delete.
   *
   * @return \Drupal\forms_steps\StepInterface
   *   The forms_steps entity.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $step_id does not exist.
   */
  public function deleteStep($step_id);

  /**
   * Deletes a progress step from the forms_steps.
   *
   * @param string $progress_step_id
   *   The progress step ID to delete.
   *
   * @return \Drupal\forms_steps\ProgressStepInterface
   *   The forms_steps entity.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $progress_step_id does not exist.
   */
  public function deleteProgressStep($progress_step_id);

  /**
   * Returns the next step to $step.
   *
   * @param \Drupal\forms_steps\Step $step
   *   The current Step.
   *
   * @return \Drupal\forms_steps\Step|null
   *   Returns the next Step or null if no next step found.
   */
  public function getNextStep(Step $step);

  /**
   * Returns the previous step to $step.
   *
   * @param \Drupal\forms_steps\Step $step
   *   The current Step.
   *
   * @return \Drupal\forms_steps\Step|null
   *   Returns the previous Step or first step if no previous step found.
   */
  public function getPreviousStep(Step $step);

  /**
   * Set the label of the delete button of the step.
   *
   * @param int $step_id
   *   Step id.
   * @param mixed $label
   *   Label to set.
   *
   * @return \Drupal\forms_steps\Entity\FormsSteps
   *   The forms steps.
   */
  public function setStepDeleteLabel($step_id, $label);

  /**
   * Set the delete state (hidden or shown) of the step.
   *
   * @param int $step_id
   *   Step id.
   * @param bool $state
   *   State to set.
   *
   * @return \Drupal\forms_steps\Entity\FormsSteps
   *   The forms steps.
   */
  public function setStepDeleteState($step_id, $state);

  /**
   * Set the label of the previous button of the step.
   *
   * @param int $step_id
   *   Step id.
   * @param mixed $label
   *   Label to set.
   *
   * @return \Drupal\forms_steps\Entity\FormsSteps
   *   The forms steps.
   */
  public function setStepPreviousLabel($step_id, $label);

  /**
   * Set the previous state (hidden or displayed) of the step.
   *
   * @param int $step_id
   *   Step id.
   * @param bool $state
   *   State to set.
   *
   * @return \Drupal\forms_steps\Entity\FormsSteps
   *   The forms steps.
   */
  public function setStepPreviousState($step_id, $state);

}
