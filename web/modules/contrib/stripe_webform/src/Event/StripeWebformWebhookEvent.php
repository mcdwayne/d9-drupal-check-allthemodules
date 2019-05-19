<?php

namespace Drupal\stripe_webform\Event;

use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a webhook related to a webform submission is received
 */
class StripeWebformWebhookEvent extends Event {

  const EVENT_NAME = StripeWebformEvents::WEBHOOK;

  /**
   * The webhook type
   *
   * @var string
   */
  public $type;

  /**
   * Webform submission entity
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  public $webform_submission;

  /**
   * Stripe API event object.
   *
   * @var \Stripe\Event
   */
  protected $event;

  /**
   * Constructs the object.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $entity
   *   The webform submission entity
   */
  public function __construct($type, WebformSubmissionInterface $webform_submission, \Stripe\Event $event) {
    $this->type = $type;
    $this->webform_submission = $webform_submission;
    $this->event = $event;
  }
}
