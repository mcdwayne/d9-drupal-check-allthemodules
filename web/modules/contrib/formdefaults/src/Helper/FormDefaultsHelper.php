<?php

namespace Drupal\Formdefaults\Helper;

use Drupal\Core\Database\Database;

/**
 * @class FormDefaultHelper
 *
 * Utility functions to help with saving / building forms etc
 */
class FormDefaultsHelper {
  function saveForm($formid, $form_array) {
    $old_form = formdefaults_getform($formid);

    // Change the input format from d6 style to d7 style.
    // Keep until D8.
    foreach ($form_array as $key => $control) {
      if (isset($control['format']) && !isset($control['input_format'])) {
      		$form_array[$key]['input_format'] = $control['format'];
      		unset($form_array[$key]['format']);
      }
    }

    $form_data = serialize($form_array);
    if ($form_data && $form_array) {
    	if ($old_form) {
        Database::getConnection()
          ->update('formdefaults_forms')
      	  ->condition('formid', $formid)
      	  ->fields(array(
      	    'formdata' => $form_data
      	  ))
      	  ->execute();
    	}
      else {
        Database::getConnection()
          ->insert('formdefaults_forms')
      	  ->fields(array(
      	    'formid' => $formid,
      	    'formdata' => $form_data,
      	  ))
      	->execute();
      }
    }
    else {
      $this->deleteForm($formid);
    }
  }

  function deleteForm($formid) {
    Database::getConnection()
      ->delete('formdefaults_forms')
      ->condition('formid', $formid)
      ->execute();
  }

  /**
   * Search for forms matching wildcard and return all those that match.
   *
   * @param String $search_str
   * @return array  Array of forms matching search criteria and their definitions
   */
  function search($search_str) {
    $search_str = '%' . $search_str . '%' ;
    $result = Database::getConnection()
      ->query('SELECT * FROM {formdefaults_forms} WHERE formid LIKE :formid', [
        ':formid' => $search_str,
      ]);
    $forms = array();
    foreach ($result as $form) {
      if ($form) {
         $formarray = unserialize($form->formdata);

         $forms[$form->formid]=$formarray;
      }
    }

    return $forms;
  }
}
