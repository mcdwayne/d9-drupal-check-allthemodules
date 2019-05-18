<?php

namespace Drupal\email_confirmer\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Email confirmation response form.
 */
class EmailConfirmerResponseForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->config('email_confirmer.settings')->get('confirmation_response.questions.' . $this->entity->getStatus());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    /** @var \Drupal\email_confirmer\EmailConfirmationInterface $confirmation */
    $confirmation = $this->getEntity();
    return $confirmation->isPending() ? $this->t('Send') : $this->t('OK');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return $this->getFormId();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_confirmer_response';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\email_confirmer\EmailConfirmationInterface $confirmation */
    $confirmation = $this->getEntity();

    $form = parent::buildForm($form, $form_state);
    unset($form['#process']);
    unset($form['#after_build']);
    // No cancel option needed.
    unset($form['actions']['cancel']);

    if ($confirmation->isPending()) {
      $form['cancel'] = [
        '#type' => 'radios',
        '#default_value' => 0,
        '#options' => [
          0 => $this->t('Confirm'),
          1 => $this->t('Cancel'),
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var \Drupal\email_confirmer\EmailConfirmationInterface $confirmation */
    $confirmation = $this->getEntity();
    $question = '';

    switch ($confirmation->getStatus()) {
      case 'pending':
        // @todo ofuscate email address if no administer permission, user is anonymous or not owner
        $question = $this->t('Confirm %email', ['%email' => $this->entity->label()]);
        break;

      case 'expired':
        if ($confirmation->isConfirmed() || $confirmation->isCancelled()) {
          $question = $this->t('Already processed');
        }
        else {
          $question = $this->t('Confirmation expired');
        }
        break;

      case 'cancelled':
        $question = $this->t('Confirmation cancelled');
        break;

      case 'confirmed':
        $question = $this->t('Confirmation done');
        break;
    }

    return $question;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\email_confirmer\EmailConfirmationInterface $confirmation */
    $confirmation = $this->getEntity();
    $hash = $this->getRouteMatch()->getParameter('hash');
    $operation = '';

    switch ($confirmation->getStatus()) {
      case 'pending':
        if ($form_state->getValue('cancel')) {
          $confirmation->cancel();
          drupal_set_message($this->t('Email confirmation cancelled.'));
          $confirmation->save();
          $operation = 'cancel';
        }
        elseif ($this->entity->confirm($hash)) {
          // Confirmed.
          drupal_set_message($this->t('Email confirmation confirmed.'));
          $confirmation->save();
          $operation = 'confirm';
        }
        else {
          drupal_set_message($this->t('There was an error processing your email confirmation.'), 'error');
          $operation = 'error';
        }

        break;

      case 'expired':
      case 'cancelled':
      case 'confirmed':
        // Go to error URL.
        $operation = 'error';

        break;
    }

    // Go to confirmation response URL, response path from settings or front.
    if ($operation) {
      $form_state->setRedirectUrl($confirmation->getResponseUrl($operation) ?: Url::fromUri('internal:/' . $this->config('email_confirmer.settings')->get('confirmation_response.url.' . $operation, '<front>')));
    }
  }

}
