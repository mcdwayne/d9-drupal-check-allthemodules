<?php

namespace Drupal\optit\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\optit\Optit\Optit;

/**
 * Confirmation form to cancel all subscriptions for the given phone number
 */
class SubscriptionCancelAllForm extends ConfirmFormBase {

  /**
   * The phone number.
   *
   * @var string
   */
  protected $phone;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'optit_subscriptions_cancel_all_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to unsubscribe phone number %phone from all keywords?', ['%phone' => $this->phone]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $phone = NULL) {
    $this->phone = $phone;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $optit = Optit::create();
    //@todo: handle exception if there is no member with given phone number.
    //@todo: handle exception if user does not have any subscriptions in a more elegant way.
    //@todo: Add success message.
    if (!$optit->subscriptionsCancelAllKeywords($this->phone)) {
      drupal_set_message($this->t('%phone could not be unsubscribed. Maybe it did not have any associated subscriptions.', array("%phone" => $this->phone)), 'warning');
    }
    if (!$_GET['destination']) {
      $form_state->setRedirect('optit.structure_members');
    }
  }

}
