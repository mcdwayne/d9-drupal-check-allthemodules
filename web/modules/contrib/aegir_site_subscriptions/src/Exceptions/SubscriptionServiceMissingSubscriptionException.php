<?php

namespace Drupal\aegir_site_subscriptions\Exceptions;

/**
 * Exception for callers using the subscription service without setting a subscription.
 */
class SubscriptionServiceMissingSubscriptionException extends ServiceMissingWrappedObjectException {
}
