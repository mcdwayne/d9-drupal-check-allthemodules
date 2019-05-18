<?php

namespace Drupal\mailjet_subscription\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides block plugin definitions for Mailjet Signup blocks.
 *
 */
class SubscriptionDerivativeBlock extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $signups = mailjet_subscription_load_multiple();

    foreach ($signups as $signup) {

        $this->derivatives[$signup->id()] = $base_plugin_definition;
        $this->derivatives[$signup->id()]['admin_label'] = t('Mailjet Subscription Form: @name', array('@name' => $signup->name));
    
    }

    return $this->derivatives;
  }

}
