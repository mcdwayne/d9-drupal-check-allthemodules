<?php

namespace Drupal\commerce_payone\Event;

/**
 * Defines events for the Commerce payone module.
 */
final class CommercePayoneEvents {

  /**
   * Name of the event fired when capture request to payone is generated.
   *
   * @Event
   */
  const PayoneCaptureRequestEvent = 'commerce_payone.capture_request';

  /**
   * Name of the event fired when preauthorization request to payone is generated.
   *
   * @Event
   */
  const PayonePreAuthRequestEvent = 'commerce_payone.preauthorization_request';

  /**
   * Name of the event fired when refund request to payone is generated.
   *
   * @Event
   */
  const PayoneRefundRequestEvent = 'commerce_payone.refund_request';

  /**
   * Name of the event fired when initialize request to payone is generated.
   *
   * @Event
   */
 const PayoneInitializeRequestEvent = 'commerce_payone.initialize_request';

}
