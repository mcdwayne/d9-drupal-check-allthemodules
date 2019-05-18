<?php

namespace Drupal\mailing_list\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for mailing list subscription form.
 *
 * @ingroup mailing_list
 */
class SubscriptionForm extends ContentEntityForm {

  /**
   * Message to the user.
   *
   * @var string
   */
  protected $message;

  /**
   * Custom form ID part.
   *
   * @var string
   */
  protected $customId;

  /**
   * Form destination.
   *
   * @var string
   */
  protected $destination;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\mailing_list\SubscriptionInterface $subscription */
    $subscription = $this->entity;
    /** @var \Drupal\mailing_list\Entity\MailingList $mailing_list */
    $mailing_list = $subscription->getList();

    $form = parent::buildForm($form, $form_state);

    // Specific title for new subscription form.
    if ($subscription->isNew() && $mailing_list) {
      $t_args = ['%name' => $mailing_list->label()];
      $form['#title'] = $this->currentUser()->hasPermission('administer mailing list subscriptions')
        ? $this->t('Add subscription to %name mailing list', $t_args)
        : $this->t('Subscribe to %name mailing list', $t_args);
    }

    // Form message.
    $message_text = $this->message ?: $mailing_list->getHelp();
    if (!empty($message_text) && $this->message != '<none>') {
      $form['message'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $message_text,
        '#weight' => -10,
      ];
    }

    // Grouping status & authoring in tabs.
    if ($this->currentUser()->hasPermission('administer mailing list subscriptions')) {
      $form['advanced'] = [
        '#type' => 'vertical_tabs',
        '#weight' => 99,
      ];

      $form['subscription_authoring'] = [
        '#type' => 'details',
        '#title' => $this->t('Subscription authoring'),
        '#open' => TRUE,
        '#group' => 'advanced',
      ];

      $form['uid']['#group'] = 'subscription_authoring';
      $form['created']['#group'] = 'subscription_authoring';

      $form['subscription_status'] = [
        '#type' => 'details',
        '#title' => $this->t('Subscription status'),
        '#open' => TRUE,
        '#group' => 'advanced',
      ];

      $form['status']['#group'] = 'subscription_status';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\mailing_list\SubscriptionInterface $entity */
    $entity = $this->entity;
    /** @var \Drupal\mailing_list\MailingListInterface $list */
    $list = $entity->getList();
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        if ($message = $list->getOnSubscriptionMessage()) {
          drupal_set_message($message);
        }
        elseif ($this->currentUser()->id() == $entity->getOwnerId()) {
          drupal_set_message($this->t('Your subscription %label to the %list mailing list has been processed. Check your email for further details.', [
            '%label' => $entity->label(),
            '%list' => $list->label(),
          ]));
        }
        else {
          drupal_set_message($this->t('Subscription %label to the %list mailing list has been processed.', [
            '%label' => $entity->label(),
            '%list' => $list->label(),
          ]));
        }

        break;

      default:
        drupal_set_message($this->t('Subscription %label to the %list mailing list has been updated.', [
          '%label' => $entity->label(),
          '%list' => $list->label(),
        ]));
    }

    // Set form destination.
    $url = $entity->toUrl($this->destination ?: 'form-destination');
    $form_state->setRedirect($url->getRouteName(), $url->getRouteParameters());
  }

  /**
   * Sets the form message. Set as '<none>' for no message at all.
   *
   * @param string $message
   *   The new message.
   */
  public function setMessage($message) {
    $this->message = $message;
  }

  /**
   * Sets a custom ID part.
   *
   * @param string $custom_id
   *   A new custom ID.
   */
  public function setCustomId($custom_id) {
    $this->customId = $custom_id;
  }

  /**
   * Sets the form destination.
   *
   * @param string $destination
   *   The new destination. One subscription entity link id, like 'canonical'.
   */
  public function setFormDestination($destination) {
    $this->destination = $destination;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    if (empty($this->customId)) {
      return parent::getFormId();
    }

    $form_id = 'mailing_list_subscription_' . $this->entity->bundle() . '_' . $this->customId;
    if ($this->operation != 'default') {
      $form_id = $form_id . '_' . $this->operation;
    }

    // Sanitize id. Removes prefixed, tailing and double "_".
    $form_id = trim(preg_replace('/_+/', '_', $form_id), '_');

    return $form_id . '_form';
  }

}
