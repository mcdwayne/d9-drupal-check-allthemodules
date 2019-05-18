<?php
namespace Drupal\maestro\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;

class MaestroAutoCompleteController extends ControllerBase {

  /**
   * Returns response for the autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */

  public function autocompleteRoles(request $request) {
    $matches = array();
    $string = $request->query->get('q');
    $roles = user_role_names(TRUE);
    foreach($roles as $rid => $name) {
      if(stristr($name, $string) !== FALSE) {
        $matches[] = $name . " ({$rid})";
      }
    }
    
    return new JsonResponse($matches);
  }
  
  public function autocompleteInteractiveHandlers(request $request) {
    $handlers = array();
    $matches = [];
    $string = $request->query->get('q');
    //let modules signal the handlers they wish to share
    $handlers = \Drupal::moduleHandler()->invokeAll('maestro_interactive_handlers', array());
    //now what are our matches based on the incoming request
    foreach($handlers as $name => $desc) {
      if(stristr($name, $string) !== FALSE) {
        $matches[] = $name;
      }
    }
    
    return new JsonResponse($matches);
  }
  
  public function autocompleteBatchHandlers(request $request) {
    $handlers = array();
    $matches = [];
    $string = $request->query->get('q');
    //let modules signal the handlers they wish to share
    $handlers = \Drupal::moduleHandler()->invokeAll('maestro_batch_handlers', array());
    //now what are our matches based on the incoming request
    foreach($handlers as $name => $desc) {
      if(stristr($name, $string) !== FALSE) {
        $matches[] = $name;
      }
    }
    
    return new JsonResponse($matches);
  }
  
}