# Amazon Simple Notification Service for Drupal

This module allows a Drupal site to respond to [Amazon
SNS](https://aws.amazon.com/sns/) notifications. For example, perhaps some
upstream system is processing a long job and the Drupal site needs to update
entity data when it's done. Instead of being forced to poll with queues and use
a background Drush command, SNS can instead push a notification to Drupal. Each
SNS message triggers a
[Symfony Event](https://drupalize.me/blog/201502/responding-events-drupal-8)
that other modules or custom code can respond to. SNS itself handles redelivery
and failure logging.

As this module requires the AWS SDK, sites using this module will need to use
Composer to grab it and all other dependencies.

This module implements it's own API as an example for other modules. To learn
how to create your own event subscribers, see
[`amazon_sns.services.yml`](http://cgit.drupalcode.org/amazon_sns/tree/amazon_sns.services.yml?h=8.x-1.x)
and
[`\Drupal\amazon_sns\Event\SnsSubscriptionConfirmationSubscriber`](http://cgit.drupalcode.org/amazon_sns/tree/src/Event/SnsSubscriptionConfirmationSubscriber.php?h=8.x-1.x).

## Setting up a subscription

This module expects all SNS notifications to be sent to `/_amazon-sns/notify`.
To test basic functionality, you can create an SNS topic and send a test
message using the Amazon Web Services console.

1. Log in to AWS and open the
   [SNS dashboard](https://console.aws.amazon.com/sns/v2/home?region=us-east-1).
1. Under `Common Actions`, create a topic.
1. In the topic, click `Create subscription`.
   * Select HTTP or HTTPS as the protocol.
   * Enter `https://example.com/_amazon-sns/notify` as the endpoint. The URL
     must be accessible to Amazon for SNS to work. Consider setting up a proxy
     like [ngrok](https://ngrok.com) or [requestb.in](https://requestb.in) for
     local environments or additional logging.
   * After saving, there should be a subscription confirmation logged in
     Drupal's logs.
1. Use `Publish to Topic` to send a test message. The message will be logged.
   You're ready to write any custom code needed to react to the notification.
