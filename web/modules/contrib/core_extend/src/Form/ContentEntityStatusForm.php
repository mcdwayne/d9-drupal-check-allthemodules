<?php

namespace Drupal\core_extend\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a generic base class for a content entity status-change form.
 */
class ContentEntityStatusForm extends ContentEntityConfirmFormBase {

  /**
   * Whether the entity should be activated or deactivated.
   *
   * @var bool
   */
  protected $activate = NULL;

  /**
   * The status field id.
   *
   * @var string
   */
  protected $statusFieldId = NULL;

  /**
   * Whether the entity should be activated or deactivated.
   *
   * @return bool
   *   True if the entity should be activated. False otherwise.
   */
  protected function activate() {
    if (is_null($this->activate)) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->getEntity();
      // Check whether entity should be activated or deactivated.
      $this->activate = ($entity->get($this->getEntityStatusFieldId())->get(0)->value == 0);
    }
    return $this->activate;
  }

  /**
   * Get the entity status field ID.
   *
   * @return null|string
   *   The entity status field ID.
   */
  protected function getEntityStatusFieldId() {
    if (is_null($this->statusFieldId)) {
      $this->statusFieldId = $this->getEntity()->getEntityType()->getKey('status');
    }
    return $this->statusFieldId;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\core_extend\Entity\EntityActiveInterface|\Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();

    // Toggle and save status of the entity.
    $entity->set($this->getEntityStatusFieldId(), $this->activate())->save();

    // Notify user and log change.
    $message = 'The @entity-type %label has been @activated.';
    $message_args = [
      '@entity-type' => $entity->getEntityType()->getLowercaseLabel(),
      '%label'       => $entity->label(),
      '@activated'   => ($this->activate()) ? 'activated' : 'deactivated',
    ];
    drupal_set_message($this->t($message, $message_args));
    $this->logger($entity->getEntityType()->getProvider())->notice($message, $message_args);
    // Redirect back to location.
    $form_state->setRedirectUrl($this->getRedirectUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t(($this->activate()) ? 'Activate' : 'Deactivate');
  }

  /**
   * Returns the URL where the user should be redirected after deletion.
   *
   * @return \Drupal\Core\Url
   *   The redirect URL.
   */
  protected function getRedirectUrl() {
    return $this->getEntity()->toUrl('canonical');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getRedirectUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('@activate the @entity-type %label?', [
      '@activate' => $this->activate() ? 'Activate' : 'Deactivate',
      '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
      '%label' => $this->getEntity()->label(),
    ]);
  }

  public function getDescription() {
    return $this->t('@activate the @entity-type %label?', [
      '@activate' => $this->activate() ? 'Activate' : 'Deactivate',
      '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
      '%label' => $this->getEntity()->label(),
    ]);
  }

}
