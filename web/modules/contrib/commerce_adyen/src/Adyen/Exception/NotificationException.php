<?php

namespace Drupal\commerce_adyen\Adyen\Exception;

/**
 * Notification is unprocessed/wrongly processed.
 *
 * This type of exception must be thrown only inside implementation of
 * "hook_commerce_adyen_notification()" and only in cases, when Adyen
 * have to be notified that notification has not been processed correctly
 * and that Adyen should put it into the queue for resending.
 *
 * @see commerce_adyen_notification()
 */
class NotificationException extends \RuntimeException {}
