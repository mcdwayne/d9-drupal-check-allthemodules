<?php

namespace Drupal\wisski_apus\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\wisski_apus\AnnotationHelper;
use Drupal\wisski_core\WisskiCacheHelper;


/**
 * @Filter(
 *   id = "wisski_apus_highlight",
 *   title = @Translation("WissKI Highlight Annotations Filter"),
 *   description = @Translation(""),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class HighlightAnnotations extends FilterBase {
  
  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    
    if (preg_match_all('/href=(?:"(?:[^"]+)"|\'(?:[^\'])+\')/u', $text, $matches)) {
      foreach (array_unique($matches[0]) as $match) {
        
        // take the URL and decode html entities
        $url = mb_substr($match, 6, -1);
        
        // do a best guess to find out whether this URL points to a
        // WissKI entity and if the URL encodes bundle information
        list($entity_url, $bundle_id) = $this->getUrlAndBundleHelper($url);
        if (empty($entity_url)) {
          continue;
        }
        
        // we add attributes to get the hiliting right
        $insert  = ' ';
        $insert .= 'href="' . $entity_url . '" ';
        $insert .= 'data-wisski-anno="oac" ';
        $insert .= 'data-wisski-target-ref="' . $url . '" ';
        $insert .= 'data-wisski-anno-bundle="' . $bundle_id . '" ';
        
        // ... and place the attrs in the element after the match
        $text = join($insert, explode($match, $text));
        
      }
    }
    
    // upgrade legacy annotations and make them processable
    if (preg_match_all('/<[^>\s]+(?:\s+[^=>]+=(?:"[^"]*"|\'[^\']*\'))*\s+class=("[^"]+"|\'[^\']+\')/u', $text, $matches)) {
dpm(array($matches, $matches[0], array_unique($matches[0])),'äää');      
      foreach (array_unique($matches[0]) as $match) {
        $tmp = array_values(explode('wisski_anno_uri_', $match, 2));
dpm($tmp);        
        if (!isset($tmp[1])) continue;        
        list($url, $rest) = preg_split('/\s/u', $tmp[1], 2);
        if (empty($rest)) {
          // we found no whitespace: this means we are at the end of the string
          // and there is only the closing ["']. this we chop off.
          $url = substr($url, 0, -1);
        }
        $url = rawurldecode($url);
dpm($url);

        // do a best guess to find out whether this URL points to a
        // WissKI entity and if the URL encodes bundle information
        list($entity_url, $bundle_id) = $this->getUrlAndBundleHelper($url);

        // we add attributes to get the hiliting right
        $insert  = ' ';
        $insert .= 'href="' . (empty($entity) ? $url : $entity->url()) . '" ';
        $insert .= 'data-wisski-anno="oac" ';
        $insert .= 'data-wisski-target-ref="' . $url . '" ';
        if (!empty($bundle_id)) $insert .= 'data-wisski-anno-bundle="' . $bundle_id . '" ';

dpm(array($insert, $match));

        // ... and place the attrs in the element after the match
        $text = join("$match $insert", explode($match, $text));
      
      }
    } else {
      dpm('no m');
    }
    
    // prepare the filter result
    $result = new FilterProcessResult($text);

    // add libraries (js+css)
    $result->addAttachments(array(
      'library' => array(
        'wisski_apus/highlight',
        'wisski_apus/infobox'
      )
    ));

    return $result;

  }

  
  /** Helper function to get the right url to link to and get the bundle id
   */
  protected function getUrlAndBundleHelper($url) {
    
    // take the URL and decode html entities
    $url = Html::decodeEntities($url);
    
    // do a best guess to find out whether this URL points to a
    // WissKI entity and if the URL encodes bundle information
    list($entity_id, $bundle_id) = AnnotationHelper::getEntityAndBundleIdFromUrl($url);
    
    // if there is no bundle information we take the last one
    // used by the user
    if (!$bundle_id) {
      $bundle_id = WisskiCacheHelper::getCallingBundle($entity_id);
    }
    
    // if there is still no bundle information, we cannot really hilite it
    if (!$bundle_id) {
      return array(NULL, NULL);
    }
    
    // we must load the entity to get the referenced url
    $entity = entity_load('wisski_individual', $entity_id);
    if (!$entity) {
      return array(NULL, NULL);
    }

    return array($entity->url(), $bundle_id);

  }

}
