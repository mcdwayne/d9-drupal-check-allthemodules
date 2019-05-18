<?php

namespace Drupal\aegir_site_subscriptions\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Aegir site subscription provider plugins.
 */
interface SubscriptionProviderInterface extends PluginInspectionInterface {

  /**
   * Redirect a form to the current user's list of subscriptions.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function redirectFormToUserSubscriptionsPage(FormStateInterface $form_state);

  /**
   * Gets the subscription URL from an ID.
   *
   * @param string $subscription_id
   *   The ID of the subscription.
   *
   * @return string
   *   The subscription URL.
   */
  public function getSubscriptionUrl($subscription_id);

  /**
   * Subscription factory method.
   *
   * @param string $subscription_id
   *   The subscription ID.
   *
   * @return object|void
   *   An instance of the subscription class if possible; NULL otherwise.
   */
  public function getSubscriptionInstance($subscription_id);

  /**
   * Returns a Drupal user ID corresponding with one in the subscription system.
   *
   * @param string $subscription_provider_user_id
   *   The user ID in the subscriptions system.
   *
   * @return int
   *   The Drupal user ID.
   */
  public function getDrupalUserId($subscription_provider_user_id);

  /**
   * Fetches the list of add-ons.
   *
   * @param object $subscription
   *   The associated subscription.
   *
   * @return array
   *   The list of add-ons.
   *
   * @throws \Exception
   *   Plugins should throw an exception if $subscription does not match
   *   the expected class.
   */
  public function getSubscriptionAddons($subscription);

  /**
   * Determines if a subscription is active.
   *
   * @param object $subscription
   *   The associated subscription.
   *
   * @return bool
   *   TRUE if the subscription is active; FALSE otherwise.
   */
  public function subscriptionIsActive($subscription);

  /**
   * Fetches a subscription's ID.
   *
   * @param object $subscription
   *   The associated subscription.
   *
   * @return string|null
   *   The ID or NULL if it's not available.
   */
  public function getSubscriptionId($subscription);

  /**
   * Fetch the subscription's plan code.
   *
   * @param object $subscription
   *   The associated subscription.
   *
   * @return string
   *   The plan code.
   */
  public function getSubscriptionPlanCode($subscription);

}
