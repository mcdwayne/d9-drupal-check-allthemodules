<?php

namespace Drupal\drd\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\drd\ContextProvider\RouteContext;

/**
 * Class EntityActions.
 *
 * @package Drupal\drd\Form
 */
class EntityActions extends Actions {

  /**
   * The route context object.
   *
   * @var \Drupal\drd\ContextProvider\RouteContext
   */
  private $context;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->context = RouteContext::findDrdContext();
    if ($this->context && $this->context->getViewMode()) {
      $this->actionService->setMode($this->context->getType());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drd_entity_action_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->context && $this->context->getViewMode()) {
      $form = parent::buildForm($form, $form_state);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->actionService->setSelectedEntities($this->context->getEntity());
    parent::submitForm($form, $form_state);
  }

}
