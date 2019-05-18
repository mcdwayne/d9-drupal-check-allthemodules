<?php

function bulk_alias_checker_op($csvdata = array(), &$context) {
    // Do heavy coding here...
    $message = 'Processing ...'.$csvdata[0];
    
    
    
    
    
    
    \Drupal::logger('checjinghjcgsajd')->notice('<pre>' . print_r($csvdata, true) . '</pre>');
    $context['message'] = $message;
    
      if (isset($csvdata[0]) && !empty($csvdata)) {
    $check = bulk_alias_checker_exist_not($csvdata[0]);

    if ($check == TRUE) {
      $message = t('URL Found: @csvdata', array('@csvdata' => $csvdata[0]));
      drupal_set_message($message);
    }
    else {
  
      $message = t('URL Not Found: @csvdata', array('@csvdata' => $csvdata[0]));
      drupal_set_message($message, 'warning');
    }
  }
    
    
}

function bulk_alias_checker_op_batch_finished($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    $message = t('Finished with an error.');
    drupal_set_message($message);
    //$_SESSION['disc_migrate_batch_results'] = $results;
}



/**
 * Implements bulk_alias_checker_exist_not().
 */
function bulk_alias_checker_exist_not($url) {
  if (bulk_alias_checker_existence($url)) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Implements bulk_alias_checker_existence().
 */
function bulk_alias_checker_existence($alias) {
  $sql = db_select('url_alias', 'ua');
  $sql->fields('ua', array('pid'));
  $sql->condition('ua.alias', '/'.$alias);
  return $sql->execute()->fetchField();
}