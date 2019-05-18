<?php

class DrealtyReconciler {
  public function __construct() {
    $this->dc = new drealtyConnection();
    $this->dm = new drealtyMetaData();
    $this->queue = DrupalQueue::get('drealty_reconciler');
  }

  /**
   * Clears the queue out.
   */
  public function clearQueue() {
    $this->queue->deleteQueue();
  }

  /**
   * Retrieves the current state of the queue.
   * @return mixed
   */
  public static function getQueue() {
    return DrupalQueue::get('drealty_reconciler');
  }

  /**
   * Updates a Drealty entity.
   * @param $item
   */
  public function processQueueItem($item) {
    $connection = $this->dc->FetchConnection($item['conid']);
    $class = $this->dm->FetchClass($item['class']);
    $resource = $this->dm->FetchResource($class->rid);
    $entity_type = $item['entity_type'];

    $field_mappings = $connection->FetchFieldMappings($resource, $class);
    $key_field = $field_mappings['rets_key']->systemname;

    $query = "({$key_field}={$item['rets_key']})";

    $params = array(
      'Format' => 'COMPACT-DECODED',
      'Limit' => "1",
      'RestrictedIndicator' => 'xxxx',
      'Count' => '0',
    );

    if($item['active'] == 0 && variable_get('drealty_reconciler_delete', 0) == 1) {
      entity_delete('drealty_listing', $item['id']);
      watchdog('drealty_reconcile', "Deleted inactive property: (MLS: @mls ID: @code)", array("@mls" => $item['rets_id'], "@code" => $item['id']), WATCHDOG_NOTICE);
    }
    else if ($this->dc->connect($connection->conid)) {
      $rets = $this->dc->rets;
      $search = $rets->SearchQuery($resource->systemname, $class->systemname, $query, $params);
      if(!$search) {
        watchdog("drealty_reconcile", 'Search for UniqueID # @key listing (MLS # @mls) was unsuccessful.', array('@key' => $item['rets_key'], '@mls' => $item['rets_id']), WATCHDOG_NOTICE);
        return FALSE;
      }
      $listing = $rets->FetchRow($search);
      $rets->FreeResult($search);
      try {
        if($wrapper = entity_metadata_wrapper('drealty_listing', $item['id'])){
          if (!$listing) {
            if(variable_get('drealty_reconciler_delete', 0) == 1) {
              entity_delete('drealty_listing', $item['id']);
              watchdog('drealty_reconcile', "Deleted inactive property: (MLS: @mls ID: @code)", array("@mls" => $item['rets_id'], "@code" => $item['id']), WATCHDOG_NOTICE);
            }
            else {
              $wrapper->active = 0;
              $wrapper->inactive_date = REQUEST_TIME;
              $property = $wrapper->value();
              entity_save('drealty_listing', $property);
            }
          }
          else {
            if ($listing && is_array($listing)) {
              $wrapper->last_reconciled = REQUEST_TIME;
              $property = $wrapper->value();
              entity_save('drealty_listing', $property);
            }
          }
        }
      } catch (Exception $e) {
        watchdog("drealty_reconcile", $e->getMessage(), array(), WATCHDOG_ERROR);
        drupal_set_message($e->getMessage(), 'warning');
//        return FALSE;
      }
      $this->dc->disconnect();
    }
  }
}