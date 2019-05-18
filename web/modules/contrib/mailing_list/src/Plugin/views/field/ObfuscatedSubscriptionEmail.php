<?php

namespace Drupal\mailing_list\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display a subscription email obfuscated.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mailing_list_subscription_obfuscated_email")
 */
class ObfuscatedSubscriptionEmail extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // No query needed.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\mailing_list\SubscriptionInterface $subscription */
    $subscription = $values->_entity;
    return $subscription->getEmail(TRUE);
  }

}
