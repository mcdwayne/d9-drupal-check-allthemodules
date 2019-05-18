<?php

namespace Drupal\content_synchronizer\Base;

/**
 * Batch Processor base.
 */
abstract class BatchProcessorBase {

  /**
   * Callback method.
   */
  abstract public static function onFinishBatchProcess($success, $results, $operations);

  /**
   * Operation callback.
   */
  abstract public static function processBatchOperation(array $oprationData, array $context);

  /**
   * Call the callback method if defined.
   */
  protected static function callFinishCallback($finishCallback = NULL, $data = NULL) {
    if (is_array($finishCallback)) {
      list($object, $method) = $finishCallback;
      if (method_exists($object, $method)) {
        if ($data) {
          $object->$method($data);
        }
        else {
          $object->$method();
        }
      }
    }
    elseif (is_string($finishCallback)) {
      if (function_exists($finishCallback)) {
        if ($data) {
          $finishCallback($data);
        }
        else {
          $finishCallback();
        }
      }
    }

  }

}
