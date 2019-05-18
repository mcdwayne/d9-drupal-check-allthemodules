<?php

namespace Drupal\matrix;

/**
 * Default controller for the matrix module.
 */
class DefaultController extends ControllerBase {

  public function matrix_custom_calculation_callback() {
    $callback = $_POST['callback'];
    $data = explode(",", $_POST['data']);
    $functions = \Drupal::moduleHandler()->invokeAll('matrix_functions');

    //ensure the callback is allowed
    if (!in_array($callback, array_keys($functions['calculation']))) {
      drupal_json_output([
        'error' => t('Calcuation callback function not available')
        ]);
      exit();
    }

    //ensure the data is safe
    foreach ($data as $id => $d) {
      $data[$id] = check_plain($d);
    }

    $result = call_user_func($callback, $data);
    drupal_json_output(['data' => check_plain($result)]);
    exit();
  }

}
