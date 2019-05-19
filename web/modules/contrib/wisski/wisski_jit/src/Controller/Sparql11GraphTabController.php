<?php

namespace Drupal\wisski_jit\Controller;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\wisski_core;
use Drupal\wisski_salz\AdapterHelper;
use Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11Engine;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\wisski_core\WisskiCacheHelper;
//optional
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
//optional end

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class Sparql11GraphTabController extends ControllerBase {

  protected $formBuilder;
 
  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }
  
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  public static function getRequest(Request $request){
    return $request;  
  }
  
  public function getJson(Request $request) {
#    drupal_set_message("got: " . serialize($request->get('wisski_individual')));
#    return new JsonResponse(array());
    //dpm("test getJson");
    $mode = $request->get('mode');

    $wisski_individual = $request->get('wisski_individual');
  
    $storage = \Drupal::entityManager()->getStorage('wisski_individual');
  
    // repair given uris
    $wisski_individual = urldecode($wisski_individual);

    $target_uri = $request->query->get('target_uri');
  

    // if it is an int, we can load the entity
    if(empty($target_uri)) {
      $entity = $storage->load($wisski_individual);
      $target_uri = AdapterHelper::getUrisForDrupalId($entity->id());
      $target_uri = current($target_uri);
    } else {
      // else it is an uri
    }
    
    //get Drupal EID
    $drupal_eid = AdapterHelper::getDrupalIdForUri($target_uri);
  
    // go through all adapters    
    $adapters = \Drupal::entityTypeManager()->getStorage('wisski_salz_adapter')->loadMultiple();
    
    // get title
    $title = NULL;
    if(!empty(WisskiCacheHelper::getCallingBundle($drupal_eid)))
      $title = wisski_core_generate_title($drupal_eid);
    
      
    $base = array("id" => $target_uri, 
                  "name" => '<span class="wki-groupname">' . $title . '</span>', 
                  "children" => array(), 
                  "data" => array(
                    "relation" => "<h2>Connections (" . $target_uri . ")</h2><ul></ul>",
                    "nodetitle"=> $title,
                  ),
            );            


    foreach ($adapters as $aid => $a) {
      $label = $a->label();
      $e = $a->getEngine();
      if ($e instanceof Sparql11Engine) {
        // full view mode        
        if($mode == 3) {
          $values = 'VALUES ?x { <' . $target_uri . '> } ';

          $q = "SELECT ?g ?s ?sp ?po ?o WHERE { $values { { GRAPH ?g { ?s ?sp ?x } } UNION { GRAPH ?g { ?x ?po ?o } } } }";
#        dpm(htmlentities($q));
          $results = $e->directQuery($q);

          foreach ($results as $result) {

            // if it is forward
            if (isset($result->sp)) {          
              $base['data']['relation'] = substr($base['data']['relation'], 0, -5);  

              $base['data']['relation'] = $base['data']['relation'] . (
                 "<li>" . $result->s->getUri() . " &raquo; " .
                 $result->sp->getUri() . "</li></ul>");
/*
              if(is_a($result->sp, "EasyRdf_Literal"))
                $base['data']['relation'] .= $result->sp->getValue() . "</li></ul>";
              else
                 $base['data']['relation'] .= $result->sp->getUri() . "</li></ul>";
*/
                $base['children'][] = array("id" => $result->s->getUri(), "name" => $result->s->localName());
                $curr = &$base['children'][count($base['children'])-1];
      
                if(empty($curr['data']['relation']))
                  $curr['data']['relation'] = ("<h2>Connections (" . $result->s->localName() .")</h2><ul></ul>");
          
//                $curr['data']['relation'] = substr($curr['data']['relation'], 0, -5);  

//                $curr['data']['relation'] = $curr['data']['relation'] . (
//                  "<li>" . $result->sp->getUri() . " &raquo; " . 
//                  $result->s->getUri() . "</li></ul>");
            } else {
              // it is backward

           $base['data']['relation'] = substr($base['data']['relation'], 0, -5);  

              if(is_a($result->o, "EasyRdf_Literal"))
                $object = $result->o->getValue();
              else
                $object = $result->o->getUri();

              $base['data']['relation'] = $base['data']['relation'] . (
                 "<li>" . $result->po->getUri() . " &raquo; " .
                 $object . "</li></ul>");

                $base['children'][] = array("id" => $object, "name" => $object);
                $curr = &$base['children'][count($base['children'])-1];
      
                if(empty($curr['data']['relation']))
                  $curr['data']['relation'] = ("<h2>Connections (" . $object .")</h2><ul></ul>");
          
#                $curr['data']['relation'] = substr($curr['data']['relation'], 0, -5);  

#                $curr['data']['relation'] = $curr['data']['relation'] . (
#                  "<li>" . $result->s->getUri() . " &raquo; " . 
#                  $result->sp->getUri() . "</li></ul>");

            }
          }
        }
        elseif ($mode == 2) {
          // standard mode

          if ($e->checkUriExists($target_uri) && $e instanceof \Drupal\wisski_adapter_sparql11_pb\Plugin\wisski_salz\Engine\Sparql11EngineWithPB) {
          
            $target_eid = AdapterHelper::getDrupalIdForUri($target_uri);

            $bundles = $e->getBundleIdsForUri($target_uri);
            $bundles_to_pbs = \Drupal::service('wisski_pathbuilder.manager')->getPbsUsingBundle();

            foreach ($bundles as $bid) {
              foreach ($bundles_to_pbs[$bid] as $pbid => $pb_info) {
                $pb = \Drupal::entityTypeManager()->getStorage('wisski_pathbuilder')->load($pbid);
                foreach ($pb->getAllPathsAndGroupsForBundleId($bid) as $path) {
                  if (!$pb->getPbPath($path->id())['enabled']) {
                    continue;
                  }
                  $q = $e->generateTriplesForPath($pb, $path, "", $target_uri);
                  $result = $e->directQuery("SELECT * { $q }");
                  //dpm($q);
                  foreach ($result as $row) {
                    $curr = &$base;
                    for ($x = 2; ; $x+=2) {
                      $xp = "x$x";
                      if (!isset($row->$xp)) break;
                      $uri = $row->$xp->getUri();
                      $eid = AdapterHelper::getDrupalIdForUri($uri);
  
                      $title = NULL;
                      if(!empty(WisskiCacheHelper::getCallingBundle($eid)))
                        $title = wisski_core_generate_title($eid);
                      
#                     drupal_set_message($xp . ' is ' . $title);
                      $drupal_url = AdapterHelper::generateWisskiUriFromId($eid);
                      $already_there = FALSE;
                      // we reuse $index below!
                      foreach ($curr['children'] as $index => $child) {
                        if ($child['id'] == $uri) {
                          $already_there = TRUE;
                          break;
                        }
                      }
                      if (!$already_there) {
                        $index = count($curr['children']);
//                      drupal_set_message($xp . ' 1is ' . $title);
                        $nodetitle = $row->$xp->localName();
                        if(!empty($title))
                        $nodetitle = $title;
                        $curr['children'][$index] = array(
                          'id' => $uri,
                          'name' => '<span class="wki-groupname" data-wisski-url="' . $drupal_url . '">' . $nodetitle . '</span>',
                          'data' => array(
                            'nodetitle' => $nodetitle,
                            //@Todo: this is a default value. change to get the appropriate value
                            'labeltext' => $nodetitle,
                            'labelid' => 'labelid=' . $nodetitle,
                          ),
                          'children' => array(),
                        );
                      }
                      $curr = &$curr['children'][$index];
                    }
                  }
                }
              }
            }
          }
        } else if ($mode == 1) {
            if ($e->checkUriExists($target_uri) && $e instanceof \Drupal\wisski_adapter_sparql11_pb\Plugin\wisski_salz\Engine\Sparql11EngineWithPB) {
              $target_eid = AdapterHelper::getDrupalIdForUri($target_uri);
              $bundles = $e->getBundleIdsForUri($target_uri);
              $bundles_to_pbs = \Drupal::service('wisski_pathbuilder.manager')->getPbsUsingBundle();
              
              foreach ($bundles as $bid) {
                foreach ($bundles_to_pbs[$bid] as $pbid => $pb_info) { 
                  $pb = \Drupal::entityTypeManager()->getStorage('wisski_pathbuilder')->load($pbid);
                  //dpm($pb->getAllPathsAndGroupsForBundleId($bid));
                  $paths = $pb->getAllPathsAndGroupsForBundleId($bid);
		  //we need the child path only
		  $path = $paths[1];  
                  //dpm($path);
                  $eid = $target_eid;
                  $pathValue = $e->pathToReturnValue($path,$pb,$eid, 0, "nodetitle");
                  //dpm($pathValue[0]['value']);
                  //$round = 1; 
                  //dpm($pathValue);
                  $index = 0 ;
                  foreach($pathValue as $value){
                    $curr = &$base;
                    //dpm($index);
                    //dpm($value);
                    //dpm($value['wisskiDisamb']);
                    //dpm($pathValue[0]['wisskiDisamb']);                                    
                    //dpm($eid);
                    if(isset($value['nodetitle'])){
                      $curr['children'][$index] = array(
                        'id' =>  $value['wisskiDisamb'] ,
                        'name' => '<span class="wki-groupname" data-wisski-url=" '. $value['wisskiDisamb'] .' "> '. $value['nodetitle'] .' </span>',
                        'data' => array(
                          'nodetitle' => $value['nodetitle'],
                          //@Todo: q.v mode=2
                          'labeltext' => $value['nodetitle'],
                          'labelid' => 'labelid=' . $value['nodetitle'],
                        ),
                        'children' => array(),
                        );
                      }
                    $index++;   
                    }
                  } 
                }
              }
            } 
          }
        } 
 
        return new JsonResponse( $base );
        
  }

  public function forward($wisski_individual) {
  //dpm($wisski_individual); 
  //$url = \Drupal\Core\Url::fromRoute('<current>', [], ['absolute' => 'true'])->toString();
  $url = base_path();
  //dpm($url);
  /*
  <div id="wki-modallink">
                  <a id="modallink" class="use-ajax" data-accepts="application/vnd.drupal-modal" href="'.$url.'wisski/navigate/
                                  '.$wisski_individual.'/modal">
                                  <span id="modallink-span>
                                  Expand
                                  </span></a>
                                                </div>
  */
  
  $form['#markup'] = '<div id="wki-graph">
              <div id="wki-modallink">
                <a id="modallink" class="use-ajax" data-accepts="application/vnd.drupal-modal" href="'.$url.'wisski/navigate/'.$wisski_individual.'/modal">
                  <span id="modallink-span">Expand</span></a>
              </div>
              <div id="wki-infocontrol">
                <select id="wki-infoswitch" size="1">
                  <option value="1">Simple View&nbsp;</option>
                  <option value="2" selected="selected">Standard View&nbsp;</option>
                  <option value="3">Full View&nbsp;</option>
                </select>
                <!--optional-->
              </div>
            <div id="wki-infovis"></div>                   
            <div id="wki-infolist"></div>
            <div id="wki-infolog"></div>
          </div>';
          
  $form['#allowed_tags'] = array('div', 'select', 'option','a');
  $form['#attached']['drupalSettings']['wisski_jit'] = $wisski_individual;
  $form['#attached']['library'][] = "wisski_jit/Jit";

          
  return $form;
  }
  
  public function openModal($wisski_individual) {        
    $response = new AjaxResponse();
    
    $modal_form = $this->formBuilder->getForm("Drupal\wisski_jit\Form\GraphModalForm", $wisski_individual);
    
    $response->addCommand(new OpenModalDialogCommand(t('Graph'),$modal_form,['width'=> '80%',
                                                                             'height'=>'550',
                                                                             'responsive'=>'true',
                                                                             'dialogClass' => 'GraphModalViewClass',]));
    
    return $response;
  }
  

/*
    $storage = \Drupal::entityManager()->getStorage('wisski_individual');

    //let's see if the user provided us with a bundle, if not, the storage will try to guess the right one
    $match = \Drupal::request();
    $bundle_id = $match->query->get('wisski_bundle');
    if ($bundle_id) $storage->writeToCache($wisski_individual,$bundle_id);

    // get the target uri from the parameters
    $target_uri = $match->query->get('target_uri');

    $entity = $storage->load($wisski_individual);
    
    // if it is empty, the entity is the starting point
    if(empty($target_uri)) {

      $target_uri = AdapterHelper::getUrisForDrupalId($entity->id());
      
      $target_uri = current($target_uri);
      
    } else // if not we want to view something else
      $target_uri = urldecode($target_uri);
      
    // go through all adapters    
    $adapters = \Drupal::entityTypeManager()->getStorage('wisski_salz_adapter')->loadMultiple();

    #$my_url = \Drupal\Core\Url::fromRoute('wisski_adapter_sparql11_pb.wisski_individual.triples', $entity->id()));

    $form['in_triples'] = array(
      '#type' => 'table',
      '#caption' => $this->t('In-coming triples'),
      '#header' => array('Subject', 'Predicate', 'Object', 'Graph', 'Adapter'),
    );
    
    $form['out_triples'] = array(
      '#type' => 'table',
      '#caption' => $this->t('Out-going triples'),
      '#header' => array('Subject', 'Predicate', 'Object', 'Graph', 'Adapter'),
    );

    foreach ($adapters as $a) {
      $label = $a->label();
      $e = $a->getEngine();
      if ($e instanceof Sparql11Engine) {
        $values = 'VALUES ?x { <' . $target_uri . '> } ';
        $q = "SELECT ?g ?s ?sp ?po ?o WHERE { $values { { GRAPH ?g { ?s ?sp ?x } } UNION { GRAPH ?g { ?x ?po ?o } } } }";
#        dpm($q);
        $results = $e->directQuery($q);
        foreach ($results as $result) {
#var_dump($result);
          if (isset($result->sp)) {
            
            $existing_bundles = $e->getBundleIdsForEntityId($result->s->getUri());

            if(empty($existing_bundles))
              $subjecturi = \Drupal\Core\Url::fromRoute('wisski_adapter_sparql11_pb.wisski_individual.triples', array('wisski_individual' => $entity->id(), 'target_uri' => $result->s->getUri() ) );
            else {
              $remote_entity_id = $e->getDrupalId($result->s->getUri());
              $subjecturi = \Drupal\Core\Url::fromRoute('wisski_adapter_sparql11_pb.wisski_individual.triples', array('wisski_individual' => $remote_entity_id, 'target_uri' => $result->s->getUri() ) );
            }

            $predicateuri = \Drupal\Core\Url::fromRoute('wisski_adapter_sparql11_pb.wisski_individual.triples', array('wisski_individual' => $entity->id(), 'target_uri' => $result->sp->getUri() ) );

            $objecturi = \Drupal\Core\Url::fromRoute('wisski_adapter_sparql11_pb.wisski_individual.triples', array('wisski_individual' => $entity->id(), 'target_uri' => $target_uri ) );

#            dpm(\Drupal::l($this->t('sub'), $subjecturi));
            $form['in_triples'][] = array(
#              "<" . $result->s->getUri() . ">",
              Link::fromTextAndUrl($this->t($result->s->getUri()), $subjecturi)->toRenderable(),
              Link::fromTextAndUrl($this->t($result->sp->getUri()), $predicateuri)->toRenderable(),
              Link::fromTextAndUrl($this->t($target_uri), $objecturi)->toRenderable(),
              array('#type' => 'item', '#title' => $result->g->getUri()),
              array('#type' => 'item', '#title' => $label),
            );
          } else {
            
            $subjecturi = \Drupal\Core\Url::fromRoute('wisski_adapter_sparql11_pb.wisski_individual.triples', array('wisski_individual' => $entity->id(), 'target_uri' => $target_uri ) );

            $predicateuri = \Drupal\Core\Url::fromRoute('wisski_adapter_sparql11_pb.wisski_individual.triples', array('wisski_individual' => $entity->id(), 'target_uri' => $result->po->getUri() ) );
            
            if($result->o instanceof \EasyRdf_Resource) {
              try {
              
                $existing_bundles = $e->getBundleIdsForEntityId($result->o->getUri());
                
                if(empty($existing_bundles))
                  $objecturi = \Drupal\Core\Url::fromRoute('wisski_adapter_sparql11_pb.wisski_individual.triples', array('wisski_individual' => $entity->id(), 'target_uri' => $result->o->getUri() ) );
                else {
                  $remote_entity_id = $e->getDrupalId($result->o->getUri());              
                  $objecturi = \Drupal\Core\Url::fromRoute('wisski_adapter_sparql11_pb.wisski_individual.triples', array('wisski_individual' => $remote_entity_id, 'target_uri' => $result->o->getUri() ) );
                }
                $got_target_url = TRUE;
              } catch (\Symfony\Component\Routing\Exception\InvalidParameterException $ex) {
                $got_target_url = FALSE;
              }
              $object_text = $result->o->getUri();
            } else {
              $got_target_url = FALSE;
              $object_text = $result->o->getValue();
            }
            $graph_uri = isset($result->g) ? $result->g->getUri() : 'DEFAULT';
            $form['out_triples'][] = array(
              Link::fromTextAndUrl($target_uri, $subjecturi)->toRenderable(),
              Link::fromTextAndUrl($result->po->getUri(), $predicateuri)->toRenderable(),
              $got_target_url ? Link::fromTextAndUrl($object_text, $objecturi)->toRenderable() : array('#type' => 'item', '#title' => $object_text),
              array('#type' => 'item', '#title' => $graph_uri),
              array('#type' => 'item', '#title' => $label),
            );
          }
        }
      }
    }
    

    $form['#title'] = $this->t('View Triples for ') . $target_uri;

    return $form;
*/
  
}
