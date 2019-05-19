<?php
/**
 * contains Drupal\wisski_core\Controller\WisskiEntityLodController
 */

namespace Drupal\wisski_core\Controller;
 
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\wisski_salz\AdapterHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WisskiEntityLodController extends ControllerBase {

  public function get() {
    
    $request = \Drupal::request();
    $params = $request->query;
    $param_preference = ['uri', 'resource', 'instance', 'q'];

    $uri = NULL;
    foreach ($param_preference as $p) { 
      if ($params->has($p)) {
        $uri = $params->get($p);
        break;
      }
    }
    
    // if no URI was given, we abort with an error
    if ($uri === NULL) {
      drupal_set_message($this->t("No URI given. You must specify a URI using one of the following query parameters: %p", ['%p' => join(", ", $param_preference)]), 'error');
      throw new NotFoundHttpException(t("No URI given."));
    }

    // cleanse URI: remove surrounding <> or expand prefix
    $uri = trim($uri);
    if (substr($uri, 0, 1) == '<' && substr($uri, -1, 1) == '>') {
      $uri = trim(substr($uri, 1, -1));
    } else {
      // TODO expand prefix
    }
    
    // check whether some adapter knows the URI
    // if not we display a page not found
    if (!AdapterHelper::checkUriExists($uri)) {
      drupal_set_message($this->t("The URI %uri is unknown to the system", ['%uri' => $uri]), 'error');
      throw new NotFoundHttpException(t("The given URI is unknown to the system."));
    }
    
    // see if it is sameas to some other uri... if so, we don't need no new eid!
    $same_uris = AdapterHelper::getPreferredLocalStore(TRUE)->getSameUris($uri);
    
    $same_uri = current($same_uris);
    
#    dpm($uri, "uri?");
#    dpm($same_uri, "same!");

    // We retrieve the URI's Drupal ID and redirect to the view
    // page. If there is no Drupal ID yet, we create one. (We know that there
    // should be one, but maybe the URI wasn't touched by Drupal, yet.)
    if(empty($same_uri))
      $eid = AdapterHelper::getDrupalIdForUri($uri, TRUE);
    else {
      $eid = AdapterHelper::getDrupalIdForUri($same_uri, FALSE);
      
      // nothing found? try the main uri
      if(empty($eid))
        $eid = AdapterHelper::getDrupalIdForUri($uri, TRUE);
        
    }    

#    dpm("found $eid");

    
    $url = Url::fromRoute(
      "entity.wisski_individual.canonical", 
      ['wisski_individual' => $eid],
      // options array; RedirectResponse expects an absolute URL
      ['absolute' => TRUE]
    );
    return new RedirectResponse($url->toString());

  }
}
