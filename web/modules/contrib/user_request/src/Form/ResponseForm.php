<?php

namespace Drupal\user_request\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user_request\Entity\RequestInterface;
use Drupal\user_request\Entity\RequestType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for the response entity.
 */
class ResponseForm extends ContentEntityForm {

  /**
   * The request being responded.
   *
   * @var \Drupal\user_request\Entity\RequestInterface
   */
  protected $request;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, RequestInterface $request = NULL) {
    parent::__construct($entity_manager);
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Attempts to get the request from the route.
    $route_match = $container->get('current_route_match');
    $request = $route_match->getParameter('user_request');
    return new static($container->get('entity.manager'), $request);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $request = $this->getUserRequest();
    $request_type = $request->getRequestType();

    // Transitions are only performed when adding a response.
    if ($this->entity->isNew()) {
      // Adds a field to select the transition to perform on top.
      $form['transition'] = [
        '#type' => 'select',
        '#title' => t('Action'),
        '#options' => [],
        '#required' => TRUE,
        '#weight' => -999,
      ];

      # Fills the transition options.
      $state_item = $request->getState();
      $transitions = $state_item->getTransitions();
      $response_transitions = $request_type->getResponseTransitions();
      foreach ($transitions as $transition_id => $transition) {
        // Only adds response form transitinos.
        if (in_array($transition_id, $response_transitions)) {
          $form['transition']['#options'][$transition_id] = $transition->getLabel();
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $request = $this->getUserRequest();

    // Saves the entity and shows a message.
    $status = parent::save($form, $form_state);
    $this->messenger()->addMessage($this->t('The response has been saved.'));

    // Adds the response to the request and performs the selected transition.
    if ($transition = $form_state->getValue('transition')) {
      $request->respond($transition, $this->entity);
      $request->save();
    }

    // Redirects to the request page.
    $form_state->setRedirectUrl($request->toUrl());

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $add_form = $route_match->getRawParameter($entity_type_id) === NULL;
    if ($add_form && $route_match->getRawParameter('user_request') !== NULL) {
      // Gets the response bundle from the request type.
      $request = $route_match->getParameter('user_request');
      $request_type = RequestType::load($request->bundle());
      $entity_storage = $this->entityTypeManager->getStorage('user_request_response');
      $entity = $entity_storage->create([
        'type' => $request_type->getResponseType(),
      ]);
    }
    else {
      $entity = parent::getEntityFromRouteMatch($route_match, $entity_type_id);
    }
    return $entity;
  }

  /**
   * Gets the request the response belongs to.
   *
   * @return \Drupal\user_request\Entity\RequestInterface
   *   The request entity.
   */
  protected function getUserRequest() {
    if (!$this->request && $this->entity) {
      // Gets the request from the response entity.
      $this->request = $this->entity->getRequest();
    }
    return $this->request;
  }

}
