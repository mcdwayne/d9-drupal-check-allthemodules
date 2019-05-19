<?php
/**
 * @file
 * Contains \Drupal\simplifycommerce\ServiceSimplifyCommerce.
 */

namespace Drupal\simplifycommerce;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class ServiceSimplifyCommerce {
  use StringTranslationTrait;

  public function __construct() {
    require_once drupal_get_path('module', 'simplifycommerce') . '/simplifycommerce-sdk-php-1.6.0/lib/Simplify.php';

    $config = \Drupal::config('simplifycommerce.settings');

    $api_mode = $config->get('api_mode') == 'live' ? 'live' : 'test';

    $public_key  = $config->get("{$api_mode}_public_key");
    $private_key = $config->get("{$api_mode}_public_key");

    if (empty($public_key) || empty($private_key)) {
      \Drupal::logger('simplifycommerce')
        ->notice($this->t('One or more API keys not set.'));
    } else {
      \Simplify::$publicKey  = $public_key;
      \Simplify::$privateKey = $private_key;
    }
  }

  /** Authorizations */
  public function createAuthorization($params = []) {
    $authorization = \Simplify_Authorization::createAuthorization($params);
    return $authorization;
  }

  public function listAuthorization($params = []) {
    $authorizations = \Simplify_Authorization::listAuthorization($params);
    return $authorizations;
  }

  public function findAuthorization($authorization_id) {
    $authorization = \Simplify_Authorization::findAuthorization($authorization_id);
    if (! $authorization->id) {
      \Drupal::logger('simplifycommerce')
        ->notice($this->t('Unable to find authorization id: @auth', ['@auth' => $authorization_id]));
    }
    return $authorization;
  }


  /** Customers */
  public function findCustomer($customer_id) {
    $customer = \Simplify_Customer::findCustomer($customer_id);
    if (! $customer->id) {
      \Drupal::logger('simplifycommerce')
        ->notice($this->t('Unable to find customer id: @customer', ['@customer' => $customer_id]));
    }
    return $customer;
  }

  public function updateCustomer($customer_id, $updates = []) {
    $customer = $this->findCustomer($customer_id);
    if ($customer->id) {
      $customer->setAll($updates);
      $customer->updateCustomer();
      return $customer;
    } else return false;
  }

  public function listCustomer($params = []) {
    $customers = \Simplify_Customer::listCustomer($params);
    return $customers;
  }

  public function deleteCustomer($customer_id) {
    $customer = \Simplify_Customer::findCustomer($customer_id);
    if ($customer->id) {
      $customer->deleteCustomer();
      return true;
    }
    return false;
  }

  public function createCustomer($params = []) {
    $customer = \Simplify_Customer::createCustomer($params);
    return $customer;
  }


  /** Refunds */
  public function createRefund($params = []) {
    $refund = \Simplify_Refund::createRefund($params);
    return $refund;
  }

  public function listRefund($params = []) {
    $refunds = \Simplify_Refund::listRefund($params);
    return $refunds;
  }

  public function findRefund($refund_id) {
    $refund = \Simplify_Refund::findRefund($refund_id);
    if (! $refund->id) {
      \Drupal::logger('simplifycommerce')
        ->notice($this->t('Unable to find refund id: @refund', ['@refund' => $refund_id]));
    }
    return $refund;
  }


  /** Subscriptions */
  public function findSubscription($subscription_id) {
    $subscription = \Simplify_Subscription::findSubscription($subscription_id);
    if (! $subscription->id) {
      \Drupal::logger('simplifycommerce')
        ->notice($this->t('Unable to find subscription id: @sub', ['@sub' => $subscription_id]));
    }
    return $subscription;
  }

  public function listSubscription($params = []) {
    $subscriptions = \Simplify_Subscription::listSubscription($params);
    return $subscriptions;
  }

  public function createSubscription($params = []) {
    $subscription = \Simplify_Subscription::createSubscription($params);
    return $subscription;
  }

  public function deleteSubscription($subscription_id) {
    $subscription = $this->findSubscription($subscription_id);
    if ($subscription->id) {
      $subscription->deleteSubscription();
      return true;
    } else return false;
  }

  public function updateSubscription($subscription_id, $updates = []) {
    $subscription = $this->findSubscription($subscription_id);
    if ($subscription->id) {
      $subscription->setAll($updates);
      $subscription->updateSubscription();
      return $subscription;
    } else return false;
  }


  /** Plans */
  public function findPlan($plan_id) {
    $plan = \Simplify_Plan::findPlan($plan_id);
    if (!$plan->id) {
      \Drupal::logger('simplifycommerce')
        ->notice($this->t('Unable to find plan id: @plan', ['@plan' => $plan_id]));
    }
    return $plan;
  }

  public function listPlan($params = []) {
    $plans = \Simplify_Plan::listPlan($params);
    return $plans;
  }

  public function createPlan($params = []) {
    $required_params = [
      'amount',
      'currency',
      'frequency',
      'frequencyPeriod',
      'name',
      'trialPeriod',
    ];

    foreach ($required_params as $param) {
      if (empty($params[$param])) {
        \Drupal::logger('simplifycommerce')
          ->notice($this->t('The @param parameter is required.', ['@param' => $param]));
        return false;
      }
    }

    return \Simplify_Plan::createPlan($params);
  }

/*  public function deletePlan($plan_id) {
    $plan = $this->findPlan($plan_id);
    if ($plan->id) {
      $plan->deletePlan();
      return true;
    } else return false;
  }
*/

  public function updatePlan($plan_id, $updates = []) {
    $plan = \Simplify_Plan::findPlan($plan_id);
    if ($plan->id) {
      $plan->setAll($updates);
      $plan->updatePlan();
      return $plan;
    } else return false;
  }
}
