<?php
/**
 * @file
 * Contains \Drupal\wisski_apus\AnnotationHelper.
 */

namespace Drupal\wisski_apus;

use DOMElement;
use Drupal\wisski_salz\AdapterHelper;

class AnnotationHelper {
  
  public static function generateAnnotationId($params = array()) {
    $id = 'wta' . \Drupal::service('uuid')->generate();
    return $id;
  }

  
  /** Tries its best to find a Wisski entity to the given URL.
   * If the URL carries any information about the group/bundle it is also
   * returned.
   *
   * @param url the URL
   * @param check perform some checks on the $url string
   *
   * @return an array with the first element the entity ID, the second element
   *   the ID of the bundle or NULL if no bundle information is found, and the
   *   third element the route name if matched. If no match can be found,
   *   returns array(NULL, NULL, NULL).
   */
  public static function getEntityAndBundleIdFromUrl($url, $check = TRUE)  {
    
    if ($check) {
      // strip whitespaces
      $url = preg_replace("/(^\s+)|(\s+$)/us", "", $url);
    }

    // if the URL has the form schema:rest, then
    // we directly ask the adapters
    if (mb_strpos($url, ':') !== FALSE) {
      $id = AdapterHelper::getDrupalIdForUri($url, NULL, FALSE);
      if ($id !== NULL) {
        return array($id, NULL, NULL);
      }
    }

    //extractEntityInfoFromRoute normally takes two parameters, but the second, $route_name, defaults to 
    // 'entity.wisski_individual.canonical' which is exactly what we want here
    return \Drupal\wisski_salz\AdapterHelper::extractEntityInfoFromRouteUrl($url);
  }



  /**
   * Return an array of annotation IDs of annotations that are contained within
   * the given element together with all elements carrying the IDs.
   *  
   * @param element the element to search in. This algorithm searches the whole
   *        subtree of elements.
   * @return an array where the keys are the found IDs nd the values are arrays
   *          containing all the elements that contain the specific ID.
   */
  public static function getAnnotationIdsWithinElement (DOMElement $element, $create_ids = TRUE) {

    $identifying_attrs = array(
      'data-wisski-target-ref',
      'data-wisski-target-type',
      'about',
      'typeof',
      'href',
    );
    
    $current = $element;
    $anno_ids = array();


    // we walk through the DOM tree, checking for each element whether it
    // it contains an annotation
    $i = 0; // for debugging
    $ascending = FALSE; // helper var for barring that we trap into an ascend-descend loop
    do {
      // check node for annotation
      // only element nodes may carry an annotation
      // the ascending test prevents us from checking the node twice
      if (!$ascending && $current->nodeType === XML_ELEMENT_NODE) {
        $id = NULL;
        // check the attributes
        if ($current->hasAttribute('data-wisski-anno-id')) {
          // first we check if there is WissKI's proprietary id attribute
          $id = $current->getAttribute('data-wisski-anno-id');
        } elseif ($create_ids || $current->hasAttribute('id') || $current->hasAttribute('name')) {
          // otherwise we check presence of other sufficient attribs
          foreach ($identifying_attrs as $attr) {
            if ($current->hasAttribute($attr)) {
              // we prioritize id over name
              $id = $current->getAttribute('name');
              $id = $current->getAttribute('id');
              if (!$id) {
                // we generate a simple uuid-based id
                $id = 'wta' . \Drupal::service('uuid')->generate();
              }
              break;
            }
          }
        }
        // populate return map with id and element
        if ($id) {
          if (!isset($anno_ids[$id])) {
            $anno_ids[$id] = [];
          }
          $anno_ids[$id][] = $current;
        }
      }
      
      // go to next element to iterate over
      if (!$ascending && $current->firstChild !== NULL) {
        // the ascending test prevents us from going into an endless loop of
        // ascending and descending: $ascending is only TRUE if we came from a
        // child node, so we must not descend again.
        $current = $current->firstChild;
        $ascending = FALSE;
      } elseif ($current->isSameNode($element)) {
        // note that there is 1 case where this if is true: on first iteration
        // if $element has no children.
        // On contrary, after descending into the children and stepping back up 
        // again, the while test will end the loop!
        break;
      } elseif ($current->nextSibling !== NULL) {
        $current = $current->nextSibling;
        $ascending = FALSE;
      } else {
        $current = $current->parentNode;
        $ascending = TRUE;
        $i++; // for debugging
      }
    } while ($current !== NULL && !$current->isSameNode($element));
    
    \Drupal::logger('annotation')->debug("dom walk ascends: $i");
    
    return $anno_ids;

  }


  public static function parseAnnotation ($anno) {
    
    if (!isset($anno->body->elements) || empty($anno->body->elements)) {
      // TODO: collect all elements from the anno->body->context and anno->id
    }
    // prepare anno object for target info
    if (!isset($anno->target)) {
      $anno->target = new \stdClass();
    }
    // track from where we have the ID
    $id_stable = isset($anno->id);
    // iterate over all elements gathering the annotation information
    foreach ($anno->body->elements as $element) {
      // get the ID if there is none set already or if it is an ID from the
      // name and id attributes, which we give lower rank
      // In rest, we take order of precedence of elements
      if (!$id_stable) {
        if ($element->hasAttribute('[data-wisski-anno-id]')) {
          $anno->id = $element->getAttribute('data-wisski-anno-id');
          $id_stable = TRUE;
        } else if ($element->hasAttribute('[name]')) {
          $anno->id = $element->getAttribute('name');
        } else if ($element->hasAttribute('[id]')) {
          $anno->id = $element->getAttribute('id');
        }
      }
      // we target an instance
      // search potential ref attributes, order resembles priority 
      $targets = '';
      if ($element->hasAttribute('data-wisski-target-ref')) {
        $targets = $element->getAttribute('data-wisski-target-ref');
      }
      elseif ($element->hasAttribute('data-wisski-target')) {
        $targets = $element->getAttribute('data-wisski-target');
      }
      elseif ($element->hasAttribute('about')) {
        $targets = $element->getAttribute('about');
      }
      elseif ($element->hasAttribute('href')) {
        $targets = $element->getAttribute('href');
      }
      // targets are potentially a ws-separated list 
      $targets = preg_replace('/\s+/u', ' ', $targets);
      $targets = explode(' ', trim($targets));
      // cleanse the list of targets
      if (!empty($targets)) {
        // for every target we check if it points to some wisski individual
        $entity_infos = array();
        foreach ($targets as $key => $target) {
          $entity_info = AnnotationHelper::getEntityAndBundleIdFromUrl($target);
          if ($entity_info[0] !== NULL) {
            $entity_infos[$target] = $entity_info;
          } else {
            unset($targets[$key]);
          }
        }
        if ($targets) {
          $anno->target->ref = $targets;
          // we also store the infos about the referred Drupal entities
          $anno->target->_entity_infos = $entity_infos;
        }
      }

      // we also send the type / category info as hasAttribute may help the
      // server to respond faster->
      // the other possibility hasAttribute that the instance is not specified,
      // and there hasAttribute just a type or category annotation->
      if ($element->hasAttribute('data-wisski-target-type')) {
        $anno->target->type = $element->getAttribute('data-wisski-target-type');
      }
      elseif ($element->hasAttribute('typeof')) {
        $anno->target->type = $element->getAttribute('typeof');
      }
      
      // there may be information about the annotator's certainty
      if ($element->hasAttribute('data-wisski-certainty')) {
        $anno->certainty = $element->getAttribute('data-wisski-certainty');
      }

    }
    
    // if this annotation does not refer to a target it's not a real annotation
    if (empty($anno->target->ref) && empty($anno->target->type)) {
      return NULL;
    }

    return $anno;

  }

}

