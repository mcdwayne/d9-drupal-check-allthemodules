<?php

namespace Drupal\membership_entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Membership edit forms.
 *
 * @ingroup membership_entity
 */
class MembershipForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $membership = $this->entity;
    $status = parent::save($form, $form_state);

    $params = ['%member_id' => $membership->getMemberID()];
    $messages = [
      SAVED_NEW => $this->t('Added Membership %member_id.', $params),
      'default' => $this->t('Saved Membership %member_id.', $params),
    ];
    if (isset($messages[$status])) {
      $this->messenger()->addMessage($messages[$status]);
    }
    else {
      $this->messenger()->addMessage($messages['default']);
    }

    $form_state->setRedirect('entity.membership_entity.canonical', ['membership_entity' => $membership->id()]);
  }
}
