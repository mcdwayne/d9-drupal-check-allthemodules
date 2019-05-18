<?php

namespace Drupal\commerce_installments\Form;

use Drupal\commerce_installments\UrlParameterBuilderTrait;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Installment Plan edit forms.
 *
 * @ingroup commerce_installments
 */
class InstallmentPlanForm extends ContentEntityForm {

  use UrlParameterBuilderTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => TRUE,
        '#weight' => 10,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->getRouteMatch()->getParameter('commerce_order');
    $entity->set('order_id', $order->id());

    // Save as a new revision if requested to do so.
    $entity->setNewRevision(FALSE);
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
      $entity->setNewRevision();
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Installment Plan.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Installment Plan.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.installment_plan.edit_form', ['installment_plan' => $entity->id()] + $this->getUrlParameters());
  }

}
