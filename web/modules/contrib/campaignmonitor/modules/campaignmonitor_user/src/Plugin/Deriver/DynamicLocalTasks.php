<?php

namespace Drupal\campaignmonitor_user\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines dynamic local tasks.
 */
class DynamicLocalTasks extends DeriverBase {
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Implement dynamic logic to provide values for the edit tab.
    $this->derivatives['campaignmonitor.user.campaignmonitor_edit_subscriptions_form'] = $base_plugin_definition;
    $this->derivatives['campaignmonitor.user.campaignmonitor_edit_subscriptions_form']['title'] = "Edit";
    $this->derivatives['campaignmonitor.user.campaignmonitor_edit_subscriptions_form']['base_route'] = "campaignmonitor.user.subscriptions";
    $this->derivatives['campaignmonitor.user.campaignmonitor_edit_subscriptions_form']['route_parameters']['user'] = 1;
    $this->derivatives['campaignmonitor.user.campaignmonitor_edit_subscriptions_form']['route_name'] = 'campaignmonitor.user.subscriptions_edit';
    return $this->derivatives;
  }

}
