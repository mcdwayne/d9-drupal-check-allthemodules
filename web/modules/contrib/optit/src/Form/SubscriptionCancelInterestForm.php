<?php

namespace Drupal\optit\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\optit\Optit\Optit;

/**
 * Confirmation form to cancel interest subscription for the given phone number.
 */
class SubscriptionCancelInterestForm extends ConfirmFormBase {

  /**
   * The phone number.
   *
   * @var string
   */
  protected $phone;

  /**
   * The interest ID.
   *
   * @var string
   */
  protected $interest_id;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'optit_subscriptions_cancel_interest_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to unsubscribe phone number %phone from interest %interest_id?', [
      '%phone' => $this->phone,
      '%interest_id' => $this->interest_id,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('optit.structure_members');
  }

  /**
   * {@inheritdoc}
   *
   * @param string $phone
   *   The phone number.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $phone = NULL, $interest_id = NULL) {
    $this->phone = $phone;
    $this->interest_id = $interest_id;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $optit = Optit::create();
    //@todo: handle exception if there is no member with given phone number.
    //@todo: handle exception if user was not subscribed to given interest in a more elegant way.
    if (!$optit->interestUnsubscribe($this->interest_id, $this->phone)) {
      drupal_set_message($this->t('%phone could not be unsubscribed %interest_id. Maybe it did not have any associated subscriptions.', [
        '%phone' => $this->phone,
        '%interest_id' => $this->interest_id,
      ]), 'warning');
    }
    else {
      drupal_set_message($this->t('Subscription cancelled successfully.'));
    }
    if (!$_GET['destination']) {
      $form_state->setRedirect('optit.structure_members');
    }
  }

}
