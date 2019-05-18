<?php

namespace Drupal\forms_steps\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\forms_steps\FormsStepsInterface;
use Drupal\forms_steps\Step;
use Drupal\forms_steps\ProgressStep;
use Drupal\Core\Url;

/**
 * FormsSteps configuration entity to persistently store configuration.
 *
 * @ConfigEntityType(
 *   id = "forms_steps",
 *   label = @Translation("FormsSteps"),
 *   handlers = {
 *     "list_builder" = "Drupal\forms_steps\Controller\FormsStepsListBuilder",
 *     "form" = {
 *        "add" = "\Drupal\forms_steps\Form\FormsStepsAddForm",
 *        "edit" = "\Drupal\forms_steps\Form\FormsStepsEditForm",
 *        "delete" = "\Drupal\Core\Entity\EntityDeleteForm",
 *        "add-step" = "\Drupal\forms_steps\Form\FormsStepsStepAddForm",
 *        "edit-step" = "\Drupal\forms_steps\Form\FormsStepsStepEditForm",
 *        "delete-step" = "\Drupal\Core\Entity\EntityDeleteForm",
 *        "add-progress-step" = "\Drupal\forms_steps\Form\FormsStepsProgressStepAddForm",
 *        "edit-progress-step" = "\Drupal\forms_steps\Form\FormsStepsProgressStepEditForm",
 *        "delete-progress-step" = "\Drupal\Core\Entity\EntityDeleteForm",
 *      }
 *   },
 *   admin_permission = "administer forms_steps",
 *   config_prefix = "forms_steps",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "redirection_policy",
 *     "redirection_target",
 *     "steps",
 *     "progress_steps",
 *   },
 *   links = {
 *     "collection" = "/admin/config/workflow/forms_steps",
 *     "edit-form" = "/admin/config/workflow/forms_steps/edit/{forms_steps}",
 *     "delete-form" =
 *   "/admin/config/workflow/forms_steps/delete/{forms_steps}",
 *     "add-form" = "/admin/config/workflow/forms_steps/add",
 *     "add-step-form" =
 *   "/admin/config/workflow/forms_steps/{forms_steps}/add_step",
 *     "add-progress-step-form" =
 *   "/admin/config/workflow/forms_steps/{forms_steps}/add_progress_step",
 *   }
 * )
 */
class FormsSteps extends ConfigEntityBase implements FormsStepsInterface {

  /**
 * Entity type id. */
  const ENTITY_TYPE = 'forms_steps';

  /**
   * The unique ID of the Forms Steps.
   *
   * @var string
   */
  public $id = NULL;

  /**
   * The label of the FormsSteps.
   *
   * @var string
   */
  protected $label;

  /**
   * The description of the FormsSteps, which is used only in the interface.
   *
   * @var string
   */
  protected $description = '';

  /**
   * The redirection policy of the FormsSteps.
   *
   * @var string
   */
  protected $redirection_policy = '';

  /**
   * The redirection target of the FormsSteps.
   *
   * @var string
   */
  protected $redirection_target = '';

  /**
   * The ordered FormsSteps steps.
   *
   * Steps array. The array is numerically indexed by the step id and contains
   * arrays with the following structure:
   *   - weight: weight of the step
   *   - label: label of the step
   *   - form_id: form id of the step
   *   - form_mode: form mode of the form of the step
   *   - url: url of the step.
   *
   * @var array
   */
  protected $steps = [];

  /**
   * The ordered FormsSteps progress steps.
   *
   * Progress steps array. The array is numerically indexed by the progress step
   * id and contains arrays with the following structure:
   *   - weight: weight of the progress step
   *   - label: label of the progress step
   *   - form_id: form id of the progress step
   *   - routes: an array of the routes for which the progress step is active
   *   - link: the link of the progress step.
   *
   * @var array
   */
  protected $progress_steps = [];

  /**
   * Returns the description.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Returns the redirection policy.
   */
  public function getRedirectionPolicy() {
    return $this->redirection_policy;
  }

  /**
   * Returns the redirection target.
   */
  public function getRedirectionTarget() {
    return $this->redirection_target;
  }

  /**
   * {@inheritdoc}
   */
  public function addStep($step_id, $label, $entityType, $entityBundle, $formMode, $url) {
    if (isset($this->Steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' already exists in the forms steps '{$this->id()}'"
      );
    }
    if (preg_match('/[^a-z0-9_]+/', $step_id)) {
      throw new \InvalidArgumentException(
        "The Step ID '$step_id' must contain only lowercase letters, numbers, and underscores"
      );
    }
    $this->steps[$step_id] = [
      'label' => $label,
      'weight' => $this->getNextWeight($this->steps),
      'entity_type' => $entityType,
      'entity_bundle' => $entityBundle,
      'form_mode' => $formMode,
      'url' => $url,
    ];
    ksort($this->steps);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addProgressStep($progress_step_id, $label, array $routes, $link, array $link_visibility) {
    if (isset($this->progress_steps[$progress_step_id])) {
      throw new \InvalidArgumentException(
        "The Progress Step '$progress_step_id' already exists in the forms steps '{$this->id()}'"
      );
    }
    if (preg_match('/[^a-z0-9_]+/', $progress_step_id)) {
      throw new \InvalidArgumentException(
        "The Progress Step ID '$progress_step_id' must contain only lowercase letters, numbers, and underscores"
      );
    }
    $this->progress_steps[$progress_step_id] = [
      'label' => $label,
      'weight' => $this->getNextWeight($this->progress_steps),
      'routes' => $routes,
      'link' => $link,
      'link_visibility' => $link_visibility,
    ];
    ksort($this->progress_steps);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasStep($step_id) {
    return isset($this->steps[$step_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function hasProgressStep($progress_step_id) {
    return isset($this->progress_steps[$progress_step_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStep(Step $step) {
    $nextStep = NULL;

    foreach ($this->getSteps() as $current_step) {
      if (is_null($nextStep)) {
        $nextStep = $current_step;
      }
      else {
        if ($nextStep->weight() < $current_step->weight()) {
          $nextStep = $current_step;

          if ($nextStep->weight() > $step->weight()) {
            break;
          }
        }
      }
    }

    if (is_null($nextStep)) {
      return NULL;
    }
    else {
      return $nextStep;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousStep(Step $step) {
    $previousStep = NULL;

    // Reverse the order of the array.
    $stepsReversed = array_reverse($this->getSteps());
    $stepsIterator = new \ArrayIterator($stepsReversed);

    while ($stepsIterator->valid()) {
      if (strcmp($stepsIterator->current()->id(), $step->id()) == 0) {
        $stepsIterator->next();
        $previousStep = $stepsIterator->current();

        break;
      }
      else {
        $stepsIterator->next();
      }
    }

    return $previousStep;
  }

  /**
   * {@inheritdoc}
   */
  public function getStepRoute(Step $step) {
    $route = 'forms_steps.' . $this->id . '.' . $step->id();

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStepRoute(Step $step) {
    $nextRoute = NULL;

    $nextStep = $this->getNextStep($step);

    if ($nextStep) {
      $nextRoute = 'forms_steps.' . $this->id . '.' . $nextStep->id();
    }

    return $nextRoute;
  }

  /**
   * Returns the previous step route.
   *
   * @param \Drupal\forms_steps\Step $step
   *   Current Step.
   *
   * @return null|string
   *   Returns the previous route.
   */
  public function getPreviousStepRoute(Step $step) {
    $previousRoute = NULL;

    $previousStep = $this->getPreviousStep($step);

    if ($previousStep) {
      $previousRoute = 'forms_steps.' . $this->id . '.' . $previousStep->id();
    }

    return $previousRoute;
  }

  /**
   * {@inheritdoc}
   */
  public function getSteps(array $step_ids = NULL) {
    if ($step_ids === NULL) {
      $step_ids = array_keys($this->steps);
    }
    /** @var \Drupal\forms_steps\StepInterface[] $steps */
    $steps = array_combine($step_ids, array_map([$this, 'getStep'], $step_ids));
    if (count($steps) > 1) {
      // Sort Steps by weight and then label.
      $weights = $labels = [];
      foreach ($steps as $id => $step) {
        $weights[$id] = $step->weight();
        $labels[$id] = $step->label();
      }
      array_multisort(
        $weights, SORT_NUMERIC, SORT_ASC,
        $labels, SORT_NATURAL, SORT_ASC
      );
      $steps = array_replace($weights, $steps);
    }
    return $steps;
  }

  /**
   * {@inheritdoc}
   */
  public function getProgressSteps(array $progress_step_ids = NULL) {
    if ($progress_step_ids === NULL) {
      $progress_step_ids = array_keys($this->progress_steps);
    }

    /** @var \Drupal\forms_steps\ProgressStepInterface[] $progress_steps */
    $progress_steps = array_combine($progress_step_ids, array_map([$this, 'getProgressStep'], $progress_step_ids));
    if (count($progress_steps) > 1) {
      // Sort Steps by weight and then label.
      $weights = $labels = [];
      foreach ($progress_steps as $id => $progress_step) {
        $weights[$id] = $progress_step->weight();
        $labels[$id] = $progress_step->label();
      }
      array_multisort(
        $weights, SORT_NUMERIC, SORT_ASC,
        $labels, SORT_NATURAL, SORT_ASC
      );
      $progress_steps = array_replace($weights, $progress_steps);
    }
    return $progress_steps;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstStep($steps = NULL) {
    if ($steps === NULL) {
      $steps = $this->getSteps();
    }
    return reset($steps);

  }

  /**
   * {@inheritdoc}
   */
  public function getLastStep($steps = NULL) {
    if ($steps === NULL) {
      $steps = $this->getSteps();
    }
    return end($steps);

  }

  /**
   * {@inheritdoc}
   */
  public function getStep($step_id) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $step = new Step(
      $this,
      $step_id,
      $this->steps[$step_id]['label'],
      $this->steps[$step_id]['weight'],
      $this->steps[$step_id]['entity_type'],
      $this->steps[$step_id]['entity_bundle'],
      $this->steps[$step_id]['form_mode'],
      $this->steps[$step_id]['url']
    );

    if (isset($this->steps[$step_id]['cancelStepMode'])) {
      $step->setCancelStepMode($this->steps[$step_id]['cancelStepMode']);
    }
    if (isset($this->steps[$step_id]['cancelRoute'])) {
      $step->setCancelRoute($this->steps[$step_id]['cancelRoute']);
    }
    if (isset($this->steps[$step_id]['submitLabel'])) {
      $step->setSubmitLabel($this->steps[$step_id]['submitLabel']);
    }
    if (isset($this->steps[$step_id]['cancelLabel'])) {
      $step->setCancelLabel($this->steps[$step_id]['cancelLabel']);
    }
    if (isset($this->steps[$step_id]['cancelStep'])) {
      $step->setCancelStep($this->getStep($this->steps[$step_id]['cancelStep']));
    }
    if (isset($this->steps[$step_id]['hideDelete'])) {
      $step->setHideDelete($this->steps[$step_id]['hideDelete']);
    }
    if (isset($this->steps[$step_id]['deleteLabel']) &&
      (!isset($this->steps[$step_id]['hideDelete']) || !$this->steps[$step_id]['hideDelete'])
    ) {
      $step->setDeleteLabel($this->steps[$step_id]['deleteLabel']);
    }
    if (isset($this->steps[$step_id]['displayPrevious'])) {
      $step->setDisplayPrevious($this->steps[$step_id]['displayPrevious']);
    }
    if (isset($this->steps[$step_id]['previousLabel'])) {
      $step->setPreviousLabel($this->steps[$step_id]['previousLabel']);
    }

    return $step;
  }

  /**
   * {@inheritdoc}
   */
  public function getProgressStep($progress_step_id) {
    if (!isset($this->progress_steps[$progress_step_id])) {
      throw new \InvalidArgumentException(
        "The progress step '$progress_step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $progress_step = new ProgressStep(
      $this,
      $progress_step_id,
      $this->progress_steps[$progress_step_id]['label'],
      $this->progress_steps[$progress_step_id]['weight'],
      isset($this->progress_steps[$progress_step_id]['routes']) ? $this->progress_steps[$progress_step_id]['routes'] : [],
      isset($this->progress_steps[$progress_step_id]['link']) ? $this->progress_steps[$progress_step_id]['link'] : '',
      isset($this->progress_steps[$progress_step_id]['link_visibility']) ? $this->progress_steps[$progress_step_id]['link_visibility'] : []
    );

    return $progress_step;
  }

  /**
   * {@inheritdoc}
   */
  public function setStepLabel($step_id, $label) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['label'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setProgressStepLabel($progress_step_id, $label) {
    if (!isset($this->progress_steps[$progress_step_id])) {
      throw new \InvalidArgumentException(
        "The progress step '$progress_step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->progress_steps[$progress_step_id]['label'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStepWeight($step_id, $weight) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['weight'] = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStepEntityBundle($step_id, $entityBundle) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['entity_bundle'] = $entityBundle;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStepUrl($step_id, $url) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['url'] = '';
    if ('/' != $url[0]) {
      $url = '/' . $url;
    }
    if (!empty(Url::fromUri("internal:$url"))) {
      $this->steps[$step_id]['url'] = $url;
    } else {
      throw new \InvalidArgumentException(
        "The Url Step '$url' is not accessible"
      );
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStepFormMode($step_id, $formMode) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['form_mode'] = $formMode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStepEntityType($step_id, $entity_type) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['entity_type'] = $entity_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStepSubmitLabel($step_id, $label) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['submitLabel'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStepCancelLabel($step_id, $label) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['cancelLabel'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStepCancelRoute($step_id, $route) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['cancelRoute'] = $route;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStepCancelStep($step_id, Step $step = NULL) {
    if (!$step) {
      return $this;
    }
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['cancelStep'] = $step;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStepCancelStepMode($step_id, $mode) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['cancelStepMode'] = $mode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStepDeleteLabel($step_id, $label) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['deleteLabel'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStepDeleteState($step_id, $state) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['hideDelete'] = $state;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setProgressStepActiveRoutes($progress_step_id, array $routes) {
    if (!isset($this->progress_steps[$progress_step_id])) {
      throw new \InvalidArgumentException(
        "The progress step '$progress_step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->progress_steps[$progress_step_id]['routes'] = $routes;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setProgressStepLink($progress_step_id, $link) {
    if (!isset($this->progress_steps[$progress_step_id])) {
      throw new \InvalidArgumentException(
        "The progress step '$progress_step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->progress_steps[$progress_step_id]['link'] = $link;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setProgressStepLinkVisibility($progress_step_id, array $steps) {
    if (!isset($this->progress_steps[$progress_step_id])) {
      throw new \InvalidArgumentException(
        "The progress step '$progress_step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->progress_steps[$progress_step_id]['link_visibility'] = $steps;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteStep($step_id) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    if (count($this->steps) === 1) {
      throw new \InvalidArgumentException(
        "The step '$step_id' can not be deleted from forms steps '{$this->id()}' as it is the only Step"
      );
    }

    unset($this->steps[$step_id]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteProgressStep($progress_step_id) {
    if (!isset($this->progress_steps[$progress_step_id])) {
      throw new \InvalidArgumentException(
        "The progress step '$progress_step_id' does not exist in forms steps '{$this->id()}'"
      );
    }

    unset($this->progress_steps[$progress_step_id]);
    return $this;
  }

  /**
   * Gets the weight for a new step or progress step.
   *
   * @param array $items
   *   An array of steps where each item has a
   *   'weight' key with a numeric value.
   *
   * @return int
   *   The weight for a step in the array so that it has the highest weight.
   */
  protected function getNextWeight(array $items) {
    return array_reduce($items, function ($carry, $item) {
      return max($carry, $item['weight'] + 1);
    }, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function status() {
    // In order for a forms_steps to be usable it must have at least one step.
    return !empty($this->status) && !empty($this->steps);
  }

  /**
   * {@inheritdoc}
   */
  public function setStepPreviousLabel($step_id, $label) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['previousLabel'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStepPreviousState($step_id, $state) {
    if (!isset($this->steps[$step_id])) {
      throw new \InvalidArgumentException(
        "The Step '$step_id' does not exist in forms steps '{$this->id()}'"
      );
    }
    $this->steps[$step_id]['displayPrevious'] = $state;
    return $this;
  }

}
