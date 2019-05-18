<?php

/**
 * @class
 * Contains all methods that have been overridden for CRUD operations for
 * Recurly Subscription Plans.
 */
class RecurlySubscriptionPlanController extends RecurlyAPIController implements RecurlyRemoteEntity {

  /**
   * Override save() to not validate the owner of subscription plans.
   *
   * @param RecurlySubscription $subscription_plan
   *   The RecurlySubscription being saved.
   * @param DatabaseTransaction $transaction
   *   Optional parameter of the transaction associated with this save.
   *
   * @return bool|int
   *   The status of the save operation.
   */
  public function save($subscription_plan, DatabaseTransaction $transaction = NULL) {
    return parent::save($subscription_plan, $transaction, FALSE);
  }

  /**
   * Overrides EntityAPIController#buildContent().
   */
  public function buildContent($entity, $view_mode = 'full', $langcode = NULL, $content = []) {
    // Add our theme functions to the build array.
    $build = parent::buildContent($entity, $view_mode, $langcode, $content);
    $build += [
      '#theme' => 'recurly_subscription_plan_' . $view_mode,
    ];

    if ($view_mode == 'recurly_data') {
      $build = $this->buildRecurlyData($build);
    }

    return $build;
  }

  /**
   * Load a Recurly Subscription Plan by it's plan code.
   *
   * @param string $plan_code
   *   The unique Recurly plan code.
   *
   * @return RecurlySubscriptionPlan
   *   The loaded recurly subscription plan, or FALSE if one is not found.
   */
  public function loadByPlanCode($plan_code) {
    $entities = $this->loadByPlanCodeMultiple([$plan_code]);
    return reset($entities);
  }

  /**
   * Load a Recurly Subscription Plans by plan codes.
   *
   * @param array $plan_codees
   *   An array of unique Recurly plan codes.
   *
   * @return array
   *   An array of loaded recurly subscription plans.
   */
  public function loadByPlanCodeMultiple(array $plan_codes) {
    $entity_ids = $this->entityIdMultiple($plan_codes);
    return $this->load($entity_ids);
  }

  /**
   * Map a Recurly Subscription Plan code to it's entity ID.
   *
   * @param string $plan_code
   *   The unique Recurly plan code.
   *
   * @return int
   *   The entity ID of the subscription plan, or FALSE if one is not found.
   */
  public function entityId($plan_code) {
    $result = $this->entityIdMultiple([$plan_code]);
    return reset($result);
  }

  /**
   * Map multiple Recurly plan codes to their entity ID.
   *
   * @param array $plan_codes
   *   An array of Recurly subscription plan codes.
   *
   * @return array
   *   An array of entity IDs, keyed by their plan code.
   */
  public function entityIdMultiple(array $plan_codes) {
    // We don't bother with the static cache here as we only key that by entity
    // ID, and not by plan code. If needed we can add a second static cache by
    // plan code later.

    return db_select('recurly_subscription_plan', 's')
      ->fields('s', ['plan_code', 'id'])
      ->condition('s.plan_code', $plan_codes, 'IN')
      ->execute()
      ->fetchAllKeyed();
  }

  /**
   * Map an Entity ID to a Recurly plan code.
   *
   * @param int $entity_id
   *   The entity ID of the subscription plan.
   *
   * @return string
   *   The unique Recurly plan code, or FALSE if one is not found.
   */
  public function recurlyCode($entity_id) {
    $result = $this->recurlyCodeMultiple([$entity_id]);
    return reset($result);
  }

  /**
   * Map multiple entity IDs to their Recurly plan codes.
   *
   * @param array $entity_ids
   *   The array of entity IDs to map.
   *
   * @return array
   *   An array of recurly plan codes.
   */
  public function recurlyCodeMultiple(array $entity_ids) {
    $plan_codes = [];

    // First, check our static entity cache for any plans that have been loaded.
    $plans = $this->cacheGet($entity_ids);

    // We don't use array_map() and a closure because array_map() copies the
    // array as of PHP 5.3. php--
    // http://willem.stuursma.name/2010/11/22/a-detailed-look-into-array_map-and-foreach/
    foreach ($plans as $id => $plan) {
      $plan_codes[$id] = $plan->plan_code;
    }

    // Now, for any IDs that aren't in our static, cache, make a database call.
    $missed_ids = array_diff($entity_ids, array_keys($plans));

    if (!empty($missed_ids)) {
      $plan_codes += db_select('recurly_subscription_plan', 's')
        ->fields('s', ['id', 'plan_code'])
        ->condition('s.id', $missed_ids, 'IN')
        ->execute()
        ->fetchAllKeyed();
    }

    return $plan_codes;
  }

  /**
   * Create a build array for the recurly_data view mode.
   *
   * Rather than manually add in our items, we build the full object and then
   * remove any data which isn't described as an entity property.
   *
   * @param array $build
   *   The build array as returned by buildContent().
   *
   * @return array
   *   A build array.
   */
  public static function buildRecurlyData($build) {
    $metadata_controller = new RecurlySubscriptionPlanMetadataController('recurly_subscription_plan');
    $info = $metadata_controller->entityPropertyInfo();
    $property_names = array_keys($info['recurly_subscription_plan']['properties']);
    foreach (element_children($build) as $key) {
      if (!in_array($key, $property_names)) {
        unset($build[$key]);
      }
    }

    return $build;
  }

  /**
   * Return the Recurly class that should be used to interact with Recurly.
   *
   * @return Recurly_Plan
   *   A Recurly_Plan class.
   */
  public function resourceClass() {
    return new Recurly_Plan();
  }

  /**
   * Overrides loadFromResource().
   *
   * "name" is a special key for machine names, so we can't use that as our
   * label. We still preserve the data, but code using subscription plan
   * entities should use label() or ->title directly.
   *
   * @param Recurly_Resource $subscription_plan
   *   The subscription plan resource that was loaded from Recurly.
   *
   * @return RecurlySubscriptionPlan
   *   The loaded Recurly Subscription Plan entity.
   */
  public function loadFromResource(Recurly_Resource $subscription_plan) {
    $subscription_plan->title = $subscription_plan->name;
    return parent::loadFromResource($subscription_plan);
  }
}
