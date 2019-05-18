<?php

namespace Drupal\external_entities\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Builds the form to delete an external entity type..
 */
class ExternalEntityTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the external entity type %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.external_entity_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    \Drupal::service('messenger')->addMessage($this->t('Deleted the %label external entity type.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    if ($route_match->getParameter($entity_type_id) !== NULL) {
      $entity_id = $route_match->getParameter($entity_type_id);
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
      if ($entity) {
        return $entity;
      }
    }

    return parent::getEntityFromRouteMatch($route_match, $entity_type_id);
  }

}
