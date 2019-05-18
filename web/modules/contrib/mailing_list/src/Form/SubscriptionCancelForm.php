<?php

namespace Drupal\mailing_list\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Builds the form to cancel (delete) subscriptions.
 */
class SubscriptionCancelForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $url = parent::getCancelUrl();

    // Try subscription page.
    if (!$url->access() && $this->entity) {
      $url = $this->entity->toUrl();
    }

    // Try user account subscriptions.
    if (!$url->access()) {
      $url = Url::fromRoute('view.mailing_list_subscriptions.page_user_subscriptions_tab', ['user' => $this->currentUser()->id()]);
    }

    // Fallback to home.
    if (!$url->access()) {
      $url = Url::fromRoute('<front>');
    }
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var \Drupal\mailing_list\SubscriptionInterface $subscription */
    $subscription = $this->getEntity();
    $t_args = ['%label' => $subscription->label(), '%list' => $subscription->getList()->label()];
    return $this->currentUser()->id() == $subscription->getOwnerId()
      ? $this->t('Are you sure you want to cancel your subscription %label to the %list mailing list?', $t_args)
      : $this->t('Are you sure you want to cancel the subscription %label to the %list mailing list?', $t_args);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    /** @var \Drupal\mailing_list\SubscriptionInterface $subscription */
    $subscription = $this->getEntity();
    /** @var \Drupal\mailing_list\MailingListInterface $list */
    $list = $subscription->getList();
    $message = $list->getOnCancellationMessage();

    return $message ?: $this->t('Your subscription %label to the %list mailing list has been cancelled.', [
      '%label' => $subscription->label(),
      '%list' => $list->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('No');
  }

}
