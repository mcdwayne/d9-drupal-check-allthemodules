<?php
/**
 * @file
 * Contains \Drupal\wisski_apus\Util.
 */

namespace Drupal\wisski_apus;


class Util {
  
  /* Extract the query arguments for a request from http parameters.
   * The function assumes that the params are encoded as JSON (object) and
   * are given in a POST/GET parameter or in the request body.
   *
   * @param query associative array with default values.
   *  If an argument is found, the recieved value will be set in $query.
   * 
   * @param param a string for the HTTP parameter to look for.
   *  If $param is NULL it will parse the request body (if present), otherwise
   *  it will look into the POST and GET params
   * 
   * @return array
   *  an array with all parameters found (not only those defined in $query)
   *  but without default values from $query
   *
   * @author Martin Scholz
   *
   */
  public function parseHttpParams(&$query, $param) {
  
    $q = NULL;

    if ($param !== NULL && isset($_POST[$param])) {
      $q = $_POST[$param];
    } elseif ($param !== NULL && isset($_GET[$param])) {
      $q = $_GET[$param];
    } elseif ($param === NULL) {
      $q = file_get_contents("php://input");
    }

    if (is_array($q) || is_object($q) || !empty($q)) {
      if (is_scalar($q)) {
        $q = json_decode($q);
      }
      if (is_array($q)) {
        $q = (object) $q;
      }
      foreach ($query as $k => $v) {
        if (isset($q->$k)) $query->$k = $q->$k;
      }
    }
    
    return $q;
  }

}

