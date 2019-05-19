<?php

namespace Drupal\workflow_participants\Form;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\RoleInterface;

/**
 * Form controller for Workflow participants edit forms.
 *
 * @ingroup workflow_participants
 */
class WorkflowParticipantsForm extends ContentEntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\workflow_participants\Entity\WorkflowParticipantsInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Restrict users to only those with the configured roles.
    $roles = $this->getParticipantRoles();
    foreach (['editors', 'reviewers'] as $key) {
      foreach (Element::children($form[$key]['widget']) as $delta) {
        if ($delta !== 'add_more') {
          $form[$key]['widget'][$delta]['target_id']['#selection_settings']['filter']['role'] = $roles;
        }
        else {
          $form[$key]['widget'][$delta]['#value'] = $this->t('Add user');
        }
      }
    }

    // Check for reviewer access and hide/lock certain fields down.
    if ($this->isReviewerOnly()) {
      $this->reviewerAccess($form, $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    // The entity type ID passed in here is always 'workflow_participants'. The
    // corresponding moderated entity needs to be determined from the route.
    $route = $route_match->getRouteObject();
    $entity_type_id = $route->getOption('_workflow_participants_entity_type');
    $entity = $route_match->getParameter($entity_type_id);
    return $this->entityTypeManager->getStorage('workflow_participants')->loadForModeratedEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;
    parent::save($form, $form_state);
    $this->messenger()->addMessage($this->t('Saved the workflow participants for %label.', [
      '%label' => $entity->getModeratedEntity()->label(),
    ]));

    // In the case of editors, they can remove themselves and no longer have
    // access. In this case, redirect.
    $this->checkAccess($form, $form_state);
  }

  /**
   * Gather a list of roles that can be participants.
   *
   * @return string[]
   *   An array of role names.
   */
  protected function getParticipantRoles() {
    $roles = array_keys(array_filter($this->entityTypeManager->getStorage('user_role')->loadMultiple(), function ($role) {
      return $role->hasPermission('can be workflow participant');
    }));

    // If the authenticated role has the permission, return empty since the
    // user selection plugin doesn't expect that role to be passed in as a
    // filter (since all users in the table have that role).
    if (in_array(RoleInterface::AUTHENTICATED_ID, $roles)) {
      return [];
    }

    return $roles;
  }

  /**
   * Lock form down for reviewers since they can only remove themselves.
   *
   * @param array $form
   *   The complete entity form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function reviewerAccess(array &$form, FormStateInterface $form_state) {
    $form['editors']['#access'] = FALSE;
    $form['reviewers']['#access'] = FALSE;

    // Remove access to submit button, and add a cancel link and a remove self
    // as reviewer button.
    $form['actions']['submit']['#access'] = FALSE;
    $form['actions']['remove_self'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove me as reviewer'),
      '#submit' => ['::removeSelf', '::submitForm', '::save', '::checkAccess'],
      '#weight' => 5,
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::cancel'],
      '#weight' => 10,
    ];

    // Display participants.
    $view_builder = $this->entityTypeManager->getViewBuilder('workflow_participants');
    $form['view_participants'] = $view_builder->view($this->entity);
  }

  /**
   * Cancel button callback.
   */
  public function cancel(array $form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->entity->getModeratedEntity()->toUrl());
  }

  /**
   * Remove self as reviewer callback.
   */
  public function removeSelf(array $form, FormStateInterface $form_state) {
    $reviewers = $form_state->getValue('reviewers');
    foreach ($reviewers as $delta => $reviewer) {
      if (is_array($reviewer) && isset($reviewer['target_id']) && ($reviewer['target_id'] == $this->currentUser()->id())) {
        unset($reviewers[$delta]);
      }
    }
    $form_state->setValue('reviewers', $reviewers);
    $this->messenger()->addMessage(t('You have been removed as a reviewer.'));
    // Redirect to canonical view. Since access may now be denied, this is
    // checked again post-save.
    $form_state->setRedirectUrl($this->entity->getModeratedEntity()->toUrl());
  }

  /**
   * Check access after save.
   *
   * User will be redirected to the front page if they no longer have access.
   */
  public function checkAccess(array $form, FormStateInterface $form_state) {
    $entity = $this->entity->getModeratedEntity();
    // @todo Entity access to the workflow participants entity should work
    // here, but that isn't sorted out. Instead call the route access callback.
    $access_checker = \Drupal::service('workflow_participants.access_checker');
    if (!$entity->access('view')) {
      $form_state->setRedirect('<front>');
    }
    elseif (!$access_checker->access($this->getRouteMatch()->getRouteObject(), $this->getRouteMatch(), $this->currentUser()) instanceof AccessResultAllowed) {
      // User might still have access to view, but not the tab. In this case,
      // redirect to the entity.
      $form_state->setRedirectUrl($entity->toUrl());
    }
  }

  /**
   * Determine if current user is only a reviewer.
   */
  protected function isReviewerOnly() {
    // Check for author.
    if ($this->entity->getModeratedEntity() instanceof EntityOwnerInterface) {
      if ($this->entity->getModeratedEntity()->getOwnerId() == $this->currentUser()->id()) {
        return FALSE;
      }
    }

    // Check for admin permissions.
    if ($this->currentUser()->hasPermission('manage workflow participants')) {
      return FALSE;
    }

    // Check for editors.
    if ($this->entity->isEditor($this->currentUser())) {
      return FALSE;
    }

    // Default to assumption of a reviewer.
    return TRUE;
  }

}
