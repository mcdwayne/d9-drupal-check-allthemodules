<?php

namespace Drupal\forms_steps;

/**
 * A value object representing a progress step.
 */
class ProgressStep implements ProgressStepInterface {

  /**
   * The forms_steps the progress step is attached to.
   *
   * @var \Drupal\forms_steps\FormsStepsInterface
   */
  protected $formsSteps;

  /**
   * The progress step's ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The progress step's label.
   *
   * @var string
   */
  protected $label;

  /**
   * The progress step's weight.
   *
   * @var int
   */
  protected $weight;

  /**
   * The progress step's active routes.
   *
   * @var string
   */
  protected $routes;

  /**
   * The progress step's link.
   *
   * @var string
   */
  protected $link;

  /**
   * The progress step's link visibility.
   *
   * @var array
   */
  protected $linkVisibility;

  /**
   * Step constructor.
   *
   * @param \Drupal\forms_Steps\FormsStepsInterface $forms_steps
   *   The forms_steps the progress step is attached to.
   * @param string $id
   *   The progress step's ID.
   * @param string $label
   *   The progress step's label.
   * @param int $weight
   *   The progress step's weight.
   * @param array $routes
   *   The progress step's active routes.
   * @param string $link
   *   The progress step's link.
   * @param array $link_visibility
   *   The progress step's link visibility.
   */
  public function __construct(FormsStepsInterface $forms_steps, $id, $label, $weight, array $routes, $link, array $link_visibility) {
    $this->formsSteps = $forms_steps;
    $this->id = $id;
    $this->label = $label;
    $this->weight = $weight;
    $this->routes = $routes;
    $this->link = $link;
    $this->linkVisibility = $link_visibility;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function weight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function activeRoutes() {
    return $this->routes;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveRoutes(array $routes) {
    return $this->routes = $routes;
  }

  /**
   * {@inheritdoc}
   */
  public function link() {
    return $this->link;
  }

  /**
   * {@inheritdoc}
   */
  public function setLink($link) {
    return $this->link = $link;
  }

  /**
   * {@inheritdoc}
   */
  public function linkVisibility() {
    return $this->linkVisibility;
  }

  /**
   * {@inheritdoc}
   */
  public function setLinkVisibility(array $steps) {
    return $this->linkVisibility = $steps;
  }

}
