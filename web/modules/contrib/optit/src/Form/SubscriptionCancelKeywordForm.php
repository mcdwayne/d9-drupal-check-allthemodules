<?php

namespace Drupal\optit\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\optit\Optit\Optit;

/**
 * Confirmation form to cancel a subscription.
 */
class SubscriptionCancelKeywordForm extends ConfirmFormBase {

  /**
   * The phone number.
   *
   * @var string
   */
  protected $phone;

  /**
   * Keyword ID.
   *
   * @var string
   */
  protected $keyword_id;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'optit_subscriptions_cancel_keyword_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to unsubscribe phone number %phone from keyword %keyword?', [
      '%phone' => $this->phone,
      '%keyword' => $this->keyword_id,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('optit.structure_keywords_subscriptions', [
      'keyword_id' => $this->keyword_id
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * @param string $phone
   *   The phone number.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $keyword_id = NULL, $phone = NULL) {
    $this->keyword_id = $keyword_id;
    $this->phone = $phone;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $optit = Optit::create();

    //@todo: handle exception if there is no member with given phone number.
    //@todo: handle exception if user was not subscribed to given keyword in a more elegant way.
    //@todo: Add success message.

    if (!$optit->subscriptionCancelByKeyword($this->phone, $this->keyword_id)) {
      drupal_set_message($this->t('%phone could not be unsubscribed from %keyword.', [
        '%phone' => $this->phone,
        '%keyword' => $this->keyword_id
      ]), 'warning');
    }

    if (!$_GET['destination']) {
      $form_state->setRedirect('optit.structure_keywords_subscriptions', [
        'keyword_id' => $this->keyword_id
      ]);
    }
  }

}
