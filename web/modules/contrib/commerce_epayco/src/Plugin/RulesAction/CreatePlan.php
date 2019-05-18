<?php

namespace Drupal\commerce_epayco\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\commerce_epayco\Entity\CommerceEpaycoApiData;

/**
 * Provides integration with Rules to create a plan.
 *
 * @RulesAction(
 *   id = "rules_commerce_epayco_create_plan",
 *   label = @Translation("Create ePayco plan"),
 *   category = @Translation("Commerce ePayco"),
 *   context = {
 *     "configuration" = @ContextDefinition("entity:commerce_epayco_api_data",
 *       label = @Translation("Configuration entity")
 *     ),
 *     "id_plan" = @ContextDefinition("string",
 *       label = @Translation("ID plan")
 *     ),
 *     "name" = @ContextDefinition("string",
 *       label = @Translation("Name")
 *     ),
 *     "description" = @ContextDefinition("string",
 *       label = @Translation("Description")
 *     ),
 *     "amount" = @ContextDefinition("string",
 *       label = @Translation("Amount")
 *     ),
 *     "currency" = @ContextDefinition("string",
 *       label = @Translation("Currency code")
 *     ),
 *     "interval" = @ContextDefinition("string",
 *       label = @Translation("Interval")
 *     ),
 *     "interval_count" = @ContextDefinition("string",
 *       label = @Translation("Interval count")
 *     ),
 *     "trial_days" = @ContextDefinition("string",
 *       label = @Translation("Trial days")
 *     )
 *   },
 *   provides = {
 *     "ep_create_plan_created" = @ContextDefinition("boolean",
 *        label = @Translation("Created")
 *     ),
 *     "ep_create_plan_message" = @ContextDefinition("string",
 *        label = @Translation("Message")
 *     ),
 *     "ep_create_plan_status" = @ContextDefinition("string",
 *        label = @Translation("Status")
 *     ),
 *     "ep_create_plan_id" = @ContextDefinition("string",
 *        label = @Translation("ID Plan")
 *     ),
 *     "ep_create_plan_customer_id" = @ContextDefinition("string",
 *        label = @Translation("User")
 *     )
 *   }
 * )
 */
class CreatePlan extends RulesActionBase {

  /**
   * Executes the plugin.
   *
   * @param \Drupal\commerce_epayco\Entity\CommerceEpaycoApiData $configuration
   *   Configuration entity. See admin/commerce/config/commerce-epayco/api-data.
   * @param string $id_plan
   *   Provided plan identifier.
   * @param string $name
   *   Provided human readable name for this plan.
   * @param string $description
   *   Provided human readable description for this plan.
   * @param string $amount
   *   Provided value to be paid for this plan.
   * @param string $currency
   *   Provided currency code, for example "COP".
   * @param string $interval
   *   Provided plan payment interval. Example: "month", "week", "day".
   * @param string $interval_count
   *   Interval counter for $interval.
   * @param string $trial_days
   *   If you will offer some free days for this plan.
   */
  protected function doExecute(CommerceEpaycoApiData $configuration, $id_plan, $name, $description, $amount, $currency, $interval, $interval_count, $trial_days) {
    $epayco = commerce_epayco_get_epayco_manager($configuration);
    $plan = $epayco->createPlan($id_plan, $name, $description, $amount, $currency, $interval, $interval_count, $trial_days);

    $this->setProvidedValue('ep_create_plan_created', isset($plan->status) ? $plan->status : FALSE);
    $this->setProvidedValue('ep_create_plan_message', isset($plan->message) ? $plan->message : '');
    $this->setProvidedValue('ep_create_plan_status', isset($plan->data->status) ? $plan->data->status : '');
    $this->setProvidedValue('ep_create_plan_id', isset($plan->data->id_plan) ? $plan->data->id_plan : '');
    $this->setProvidedValue('ep_create_plan_customer_id', isset($plan->data->user) ? $plan->data->user : '');
  }

}
