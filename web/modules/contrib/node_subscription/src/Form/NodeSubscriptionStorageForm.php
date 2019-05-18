<?php

namespace Drupal\node_subscription\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Node subscription edit forms.
 *
 * @ingroup node_subscription
 */
class NodeSubscriptionStorageForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\node_subscription\Entity\NodeSubscriptionStorage */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Node subscription.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Node subscription.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.node_subscription_storage.canonical', ['node_subscription_storage' => $entity->id()]);
  }

}
