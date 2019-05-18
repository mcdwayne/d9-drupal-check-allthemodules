<?php

namespace Drupal\invite_by_email\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\invite\Entity\Invite;

/**
 * Class InviteByEmailBlockForm.
 *
 * @package Drupal\invite\Form
 */
class InviteByEmailBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invite_by_email_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $invite_type = $this->config('invite.invite_type.' . $form_state->getBuildInfo()['args'][0]);
    $data = unserialize($invite_type->get('data'));

    $form['email'] = [
      '#type' => 'email',
      '#required' => TRUE,
      '#title' => $this->t('Email'),
    ];

    if (!$data['use_default'] && $data['subject_editable']) {
      $invite_email_subject_default = \Drupal::service('entity_field.manager')->getFieldDefinitions('invite', 'invite')['field_invite_email_subject']->getDefaultValueLiteral()[0]['value'];

      $form['email_subject'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Email subject'),
        '#default_value' => $invite_email_subject_default,
      ];
    }

    $form['send_invitation'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Invitation'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $user = \Drupal::currentUser();

    $mail = $user->getEmail();

    $values = $form_state->getValues();

    if (!empty($values['email']) && $values['email'] == $mail) {
      $form_state->setErrorByName('email', $this->t("You couldn't invite yourself."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $invite_type = $form_state->getBuildInfo()['args'][0];
    $invite = Invite::create(['type' => $invite_type]);
    $invite->field_invite_email_address->value = $form_state->getValue('email');
    $subject = $form_state->getValue('email_subject');
    if (!empty($subject)) {
      $invite->field_invite_email_subject->value = $subject;
    }
    $invite->setPlugin('invite_by_email');
    $invite->save();
  }

}
