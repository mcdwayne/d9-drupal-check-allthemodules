<?php
/**
 * @file
 * Contains \Drupal\wisski_apus\Controller\InfoboxController.
 */
 
namespace Drupal\wisski_apus\Controller;
 
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\HtmlResponse;
use Drupal\wisski_salz\AdapterHelper;
use Drupal\wisski_core\WisskiStorage;
use Drupal\wisski_core\WisskiCacheHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

 
class InfoboxController extends ControllerBase {
  
  
  public function labels () {

    $anno = $_GET['anno'];
    $anno = \Drupal::request()->query->get('anno');
    $titles = array();

    if (isset($anno['target']['ref'])) {
      $refs = (array) $anno['target']['ref'];
      foreach ($refs as $uri) {
        $entity_id = AdapterHelper::getDrupalIdForUri($uri, NULL, FALSE);
        $titles[$entity_id] = $uri;
        $label = WisskiCacheHelper::getEntityTitle($entity_id);
        $titles[$uri] = $label;
      }
    }

    $response = new JsonResponse($titles);
    return $response;

  }

  
  public function content () {
      
    $anno = $_GET['anno'];
    $anno = \Drupal::request()->query->get('anno');
    $content = NULL;
    
    if (isset($anno['target']['ref'])) {
      
      $content = $this->refContent($anno);
    } elseif (isset($anno['target']['type'])) {
      $content = $this->typeContent($anno);
    } else {
      $content = $this->t('No information available.');
    }

    // TODO: don't cache!
    $response = new HtmlResponse($content);

    return $response;
      
  }
  

  private function typeContent($anno) {
    return $this->t('This annotation points to an unspecified instance classified as %c.', array('%c' => $anno['target']['type']));
  }


  private function refContent($anno) {
    
    $uri = $anno['target']['ref'];
    // TODO: we currently handle only one entity per annotation
    if (is_array($uri)) $uri = $uri[0];
    $id = AdapterHelper::getDrupalIdForUri($uri);

    if (!$id) {
      return $this->t('Unknown URI.');
    }

    
/*    $indiv = entity_load('wisski_individual', $id);
    $view = entity_view($indiv, 'wisski_individual.infobox');
    
    $content = \Drupal::service('renderer')->render($view);
    
    return $content;
*/    
    $image = WisskiCacheHelper::getPreviewImageUri($id);
    $label = WisskiCacheHelper::getEntityTitle($id);
    $uris = AdapterHelper::getUrisForDrupalId($id);
    // if there is no label we crete a fallback
    if (!$label) $label = "$id/" . $uris[0] ;



    $result =  '<h3>' . $label . '</h3><img src="' . $image . '" /><ul>';
    foreach ($uris as $uri) {
      $result .= "<li><a href=\"$uri\">$uri</a></li>";
    }
    $result .= "</ul>";
    return $result;
    return array(
      'label' => array(
        '#value' => '<h3>' . $label . '</h3>',
      ),
      'image' => array(
        '#value' => '<img src="' . $image . '" />',
      ),
    );


    return $this->t(
      'This annotation points to instance %i@c.', 
      array(
        '%i' => $label,
        '@c' => isset($anno['targetType']) ?
                $this->t(' and is classified as %c', array('%c' => $anno['target']['Type'])) :
                '',
      )
    );

  }


}


