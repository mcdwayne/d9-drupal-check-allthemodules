<?php

namespace Drupal\amazon_sns\Event;

/**
 * Events for SNS messages.
 *
 * These event types are set in the inbound x-amz-sns-message-type header.
 *
 * @see http://docs.aws.amazon.com/sns/latest/dg/json-formats.html
 */
final class SnsEvents {

  /**
   * Event sent when a new subscription to a topic needs to be confirmed.
   *
   * This matches the SubscriptionConfirmation message type.
   *
   * @Event
   *
   * @var string
   */
  const SUBSCRIPTION_CONFIRMATION = 'amazon_sns_subscription_confirmation';

  /**
   * Event sent when a notification is sent to a topic subscriber.
   *
   * This matches the Notification message type.
   *
   * @Event
   *
   * @var string
   */
  const NOTIFICATION = 'amazon_sns_notification';

  /**
   * Event sent when unsubscribing from a topic.
   *
   * This matches the UnsubscribeConfirmation message type.
   *
   * @Event
   *
   * @var string
   */
  const UNSUBSCRIBE_CONFIRMATION = 'amazon_sns_unsubscribe_confirmation';

}
