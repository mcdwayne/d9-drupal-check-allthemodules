<?php

namespace Drupal\wisski_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\wisski_core\WisskiBundleInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use Drupal\wisski_core\WisskiCacheHelper;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\views\Entity\View;

use Drupal\Core\Entity\EntityStorageInterface;
/**
 * Defines the bundle configuration entity.
 *
 * @ConfigEntityType(
 *   id = "wisski_bundle",
 *   label = @Translation("Wisski Bundle"),
 *	 fieldable = FALSE,
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\wisski_core\Form\WisskiBundleForm",
 *       "edit" = "Drupal\wisski_core\Form\WisskiBundleForm",
 *			 "delete" = "Drupal\wisski_core\Form\WisskiBundleDeleteForm",
 *       "title" = "Drupal\wisski_core\Form\WisskiTitlePatternForm",
 *			 "delete_title" = "Drupal\wisski_core\Form\WisskiTitlePatternDeleteForm",
 *     },
 *     "list_builder" = "Drupal\wisski_core\Controller\WisskiBundleListBuilder",
 *     "access" = "Drupal\wisski_core\Controller\WisskiBundleAccessHandler",
 *   },
 *   admin_permission = "administer wisski_core",
 *   config_prefix = "wisski_bundle",
 *   config_export = {
 *     "id",
 *     "label",
 *     "title_pattern",
 *     "on_empty",
 *     "fallback_title",
 *     "pager_limit",
 *     "menu_items",
 *   },
 *
 *   bundle_of = "wisski_individual",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "description" = "description",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/wisski_core/{wisski_bundle}/edit",
 *     "delete-form" = "/admin/structure/wisski_core/{wisski_bundle}/delete",
 *     "entity-list" = "/wisski/navigate/{wisski_bundle}",
 *     "list" = "/admin/structure/wisski_core",
 *     "title-form" = "/admin/structure/wisski_core/{wisski_bundle}/title",
 *     "delete-title-form" = "/admin/structure/wisski_core/{wisski_bundle}/delete-title",
 *   }
 * )
 */
class WisskiBundle extends ConfigEntityBundleBase implements WisskiBundleInterface {
  
  use StringTranslationTrait;
  
  /** constants to identify empty title reaction types */
  const DONT_SHOW = 1;
  const FALLBACK_TITLE = 2;
  const DEFAULT_PATTERN = 3;

  const MENU_CREATE = 1;
  const MENU_ENABLE = 2;

  /**
   * A pb cache because loading is pain
   */
  protected $pb_cache = array();
  
  /**
   * An adapter cache because loading is pain!
   */ 
  protected $adapter_cache = array();
  
  /**
   * The field based pattern for the entity title generation.
   * A serialized array.
   * @var string
   */
  protected $title_pattern = '';
  
  /**
   * The way in which to react on the detection of an invalid title
   * defaults to fallback title
   */
  protected $on_empty = self::DEFAULT_PATTERN;
  
  /**
   * The fallback title that may be shown when an entity title cannot be resolved
   */
  protected $fallback_title = 'WissKI Entity';
  
  /**
   * The pager limit for the bundle based entity list
   */
  protected $pager_limit = 10;
  
  /**
   * The options array for this bundle's title pattern
   */
  protected $path_options = array();


  protected $menu_items = array();
  
  /**
   * Where should this be listed?
   * @return array with key = menu name
   *         and value route parameters
   */
  public static function getWissKIMenus() {
    return array('navigate' => 'entity.wisski_bundle.entity_list',
                 'create' => 'entity.wisski_individual.add');
  }
  
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

#    $menus = array("navigate" => 'entity.wisski_bundle.entity_list', "create" => 'entity.wisski_individual_create.list');
    
    foreach($entities as $entity) {
      $menus = $entity->getWissKIMenus();
      foreach($menus as $menu_name => $route) {
        $entity->deleteBundleFromMenu($menu_name,  $route);
      }
    }
  }
  
  public function getTitlePattern() {

    if(empty($this->title_pattern)) {

      $state = \Drupal::state()->get('wisski_core_title_patterns') ?: serialize(array());
      $state = unserialize($state);

      $title = isset($state[$this->id]) ? $state[$this->id] : '';

      if(!empty($title))
        return $title;
    }

    return unserialize($this->title_pattern);

  }
  
  public function removeTitlePattern() {

    if ('' !== $this->title_pattern) {
      $this->title_pattern = '';
      $this->flushTitleCache(); 
    }
  }
  
  public function getDefaultPattern() {
    
    return \Drupal::config('wisski_core.settings')->get('wisski_default_title_pattern');
  }
  
  protected $cached_titles;
  
  public function generateEntityTitle($entity,$include_bundle=FALSE,$force_new=FALSE) {
#    dpm(microtime(), "begin title");
    $pattern = $this->getTitlePattern();

    // reduce to the id because for historical reasons...
    if(is_object($entity))
      $entity_id = $entity->id();
    else
      $entity_id = $entity;


    #drupal_set_message(serialize($pattern));
    #drupal_set_message("generated: " . $this->applyTitlePattern($pattern,$entity_id));
#    dpm([$pattern, $entity_id], "eid!");
#    dpm(serialize($force_new), "force new?");
    if (!$force_new) {
      $title = $this->getCachedTitle($entity_id);
 #     dpm(microtime(), "got cached title " . serialize($title) . "for entity $entity with pattern " . serialize($pattern));
      if (isset($title)) {
        #drupal_set_message('Title from cache');
        if ($include_bundle) {
          drupal_set_message('Enhance Title '.$title);
          $title = $this->label().': '.$title;
        }    
        return $title;
      }
    }
    
#    dpm([$pattern, $entity_id], "eid!");
    
    $pattern = $this->getTitlePattern();
    
    //now do the work
    $title = $this->applyTitlePattern($pattern,$entity);
    
    if(!empty($entity_id))
      $this->setCachedTitle($entity_id,$title);
    
    if ($include_bundle && $title !== FALSE) {
      drupal_set_message('Enhance Title '.$title);
      $title = $this->label().': '.$title;
    }   
    
#    dpm(microtime(), "generated title $title");
    
#    dpm(microtime(), "end title");
    return $title;
  }
  
  /**
   * Applies the title pattern to generate the entity title,
   * this is a seperate function since we want to be able to apply it again in case we end up with an empty title
   */
  private function applyTitlePattern($pattern,$entity) {
 #   dpm(microtime(), "apply");
    // reduce to the id because for historical reasons...
    if(is_object($entity))
      $entity_id = $entity->id();
    else
      $entity_id = $entity;
    
#    dpm($pattern,__FUNCTION__);
    if(isset($pattern['max_id']))
      unset($pattern['max_id']);
        
    // just in case...
    if (empty($pattern)) return $this->createFallbackTitle($entity_id);;
    
    $parts = array();
    $pattern_order = array_keys($pattern);
    //just to avoid infinite loops we introduce an upper bound,
    //this is possible since per run at most k-1 other elements have to be cycled through before
    //having seen all parents i.e. $max = sum_{k = 0}^$count k
    $count = count($pattern);
    $max = ($count * ($count+1)) / 2;
    $count = 0;
    while ($count < $max && current($pattern) ) { //&& list($key,$attributes) = each($pattern)) {
      
      $key = key($pattern);
      $attributes = current($pattern);
            
      $count++;
      unset($pattern[$key]);
      reset($pattern);
      //dpm($pattern,'Hold '.$key);
      //if we have a dependency make sure we only consider this one, when all dependencies are clear
      if (!empty($attributes['parents'])) {
        foreach ($attributes['parents'] as $parent => $positive) {
          //dpm($parts,'Ask for '.$parent.' '.($positive ? 'pos' : 'neg'));
          if (!isset($parts[$parent])) {
            $pattern[$key] = $attributes;
            continue 2;
          } elseif ($positive) {
            if ($parts[$parent] === '') continue 2;
          } else { //if negative
            if (!empty($parts[$parent])) continue 2;
          }
        }
      }
      if ($attributes['type'] === 'path') {
        $name = $attributes['name'];
        unset($values);
        switch ($name) {
          case 'eid':
            $values = array($entity_id);
            break;
          case 'uri.long':
          case 'uri.short':
            $values = array($this->getUriString($entity_id,$name));
            break;
          case 'bundle_label':
            $values = array($this->label());
            break;
          case 'bundle_id':
            $values = array($this->id());
            break;
          default: {
            list($pb_id,$path_id) = explode('.',$attributes['name']);
            $values = $this->gatherTitleValues($entity, $path_id, $pb_id);
#            dpm($values,'gathered values for '.$path_id);
          }
        }
        if (empty($values)) {
          if ($attributes['optional'] === FALSE) {
            //we detected an invalid title;
            drupal_set_message('Detected invalid title','error');
            return $this->createFallbackTitle($entity_id);
          } else $parts[$key] = '';
          continue;
        }
        $part = '';
        $cardinality = $attributes['cardinality'];
        if ($cardinality < 0 || $cardinality > count($values)) $cardinality = count($values);
        $delimiter = $attributes['delimiter'];
        $i = 0;
#        dpm($values, "values");
        foreach ($values as $value) {
          // fix for empty values, we ignore these for now.
          // a numeric 0 oder the string "0" also is empty, but we want to 
          // print them as is
          if(empty($value) && $value !== 0 && $value !== "0")
            continue;
#          dpm($i, "i");
#          dpm($cardinality, "card");
          if ($i >= $cardinality) break;
#          dpm($value, 'get');
          $part .= "$value";
          if (++$i < $cardinality) $part .= $delimiter;
        } 
      }
      if ($attributes['type'] === 'text') {
        $part = $attributes['label'];
      }
      //if (!empty($attributes['children'])){dpm($part,'Part');dpm($parts,'Parts '.$key);}
      
      $parts[$key] = $part;
    }
#    dpm(array('parts'=>$parts),'after');
    
    //reorder the parts according original pattern
    $title = '';
    foreach ($pattern_order as $pos) {
      if (isset($parts[$pos])) $title .= $parts[$pos];
    }
    
    if (empty(trim($title))) return $this->createFallbackTitle($entity_id);

    #dpm(func_get_args()+array('result'=>$title),__METHOD__);
    return $title;
  }
  
  public function createFallbackTitle($entity_id) {
    
    switch ($this->onEmpty()) {
      case self::FALLBACK_TITLE: return $this->fallback_title;
      case self::DEFAULT_PATTERN: return $this->applyTitlePattern($this->getDefaultPattern(),$entity_id);
      case self::DONT_SHOW:
      default: return FALSE;
    }
  }
  
  public function gatherTitleValues($eid, $path_id, $pb_id = NULL) {
    #dpm("yay!");
    $values = array();
#    dpm(microtime(), "gather");
    $moduleHandler = \Drupal::service('module_handler');
    if (!$moduleHandler->moduleExists('wisski_pathbuilder')){
      return NULL;
    }
#    dpm(serialize($eid), "eid!!");
    
    // this is the case for create-dialog-thingies where the id is still empty
    if(is_object($eid) ) {
      if(empty($eid->id())) {
      
        // early opt out if it is not a new entity but we dont have the entity id - which probably is bad!
        if(!$eid->isNew()) {
          return;
        }
      
        // try to build it with the values at hand...
#        dpm(serialize($eid), "eid!!");
        if(!empty($pb_id)) {
          if(isset($this->pb_cache[$pb_id]))
            $pb = $this->pb_cache[$pb_id];
          else {
#            dpm(microtime(), "pb cache is not set!");
            $this->pb_cache = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple();
            $pb = $this->pb_cache[$pb_id];
          }
           
          $path = $pb->getPbPath($path_id);
          
          if(isset($path['field'])) {
                        
            if(!$eid->hasField($path['field'])) {
              return;
            }
            
            // get all the values from the current entity
            $values = $eid->get($path['field'])->getValue();

            if(empty($values)) {
              return;
            }
            
            $out_values = array();
            
            // go in there and gather these
            foreach($values as $value) {
                // what is the main prop
                // hopefully we dont need that
                // $mainprop = 'value';
              if(isset($value['value']))
                $out_values[] = $value['value'];
              else {
                // ??
              }
                
            }
            
            // return what we've got
            return $out_values;
          }
        } else {
          drupal_set_message("This should not happen! WissKI Bundle Title Generation error", "error");
        }
      } else {
        $eid = $eid->id();
      }
    }                  

    if(empty($this->pb_cache)) {
#      dpm(microtime(), "pb cache was not set!");
      $this->pb_cache = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple();
#      dpm(microtime(), "pb cache was not set2!");
    }
    
    $pbs = $this->pb_cache;

    if(empty($this->adapter_cache)) {      
      $this->adapter_cache = \Drupal\wisski_salz\Entity\Adapter::loadMultiple();
    }
    
    $adapters = $this->adapter_cache;
    //we ask all pathbuilders if they know the path
    foreach ($pbs as $pb_id => $pb) {
      if ($pb->hasPbPath($path_id)) {
        // if the PB knows the path we try to load it
        $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($path_id);
        if (empty($path)) {
          //dpm('can\'t load path '.$path_id,$pb_id);
          continue;
        }
        #dpm($path,$path_id);
        // then we try to load the path's adapter
        $adapter = $adapters[$pb->getAdapterId()];
        if (empty($adapter)) {
          #dpm('can\'t load adapter '.$pb->getAdapterId(),$pb_id);
          continue;
        }
        
        // only do this if there is data!
        if (\Drupal\wisski_salz\AdapterHelper::getUrisForDrupalId($eid, $adapter->id(), FALSE)) {
          //finally, having a valid path and adapter, we can ask the adapter for the path's value
          $pbpath = $pb->getPbPath($path_id);

#          dpm($pbpath, "pbp");
          
          // this is the case when the thing is a group
          // in this case we want it to generate the title of the
          // subentity and use that.
          if($pbpath['bundle'] == $pbpath['field'] || $pbpath['fieldtype'] == "entity_reference") {

            // get the data from the pathbuilder
            
            // in case of entity_reference which is not a group, be absolute!
            if($pbpath['fieldtype'] == "entity_reference" && $pbpath['bundle'] != $pbpath['field']) {
              // in case of entity reference this may not be absolute... I don't know why it was
              // use case: Edit form with some sub-value field and there is an entity reference in it. 
              // Then we may not do this here. Example is divination historische einordnung
              $tmp = $adapter->getEngine()->pathToReturnValue($path, $pb, $eid, 0, "target_id", TRUE);
            } else {
              $tmp = $adapter->getEngine()->pathToReturnValue($path, $pb, $eid, 0, "target_id", TRUE);
            }
#            dpm($pbpath, "pbp");
#            dpm($tmp, "tmp");
#            dpm($path, "path");
#            dpm($pb, "pb");
#            dpm($eid, "eid");
  // absolut?
            $grptitles = array();

            // iterate through the data we've got
            foreach($tmp as $key => $item) {
              
              // construct the drupal id
              $item_eid = $adapter->getEngine()->getDrupalId($item["target_id"]);
              
              // get the bundle of the data
              $bundleid = $pb->getBundleIdForEntityId($item_eid);
              if(empty($bundleid))
                continue;
              
              $bundle = \Drupal\wisski_core\Entity\WisskiBundle::load($bundleid);
              
              if(empty($bundle))
                continue;
              
              // generate the title of that
              $grptitles[] = $bundle->generateEntityTitle($item_eid);
              
            }
            
            $new_values[] = implode(", ", $grptitles);

          } else { // normal field handling
      #      dpm("normal handling1");                  
            $bundle_of_path = $pbpath['bundle'];
 
            // if this is empty, then we get the parent and take this.
            if(empty($bundle_of_path) || $path->getType() == "Path") {
              $group = $pb->getPbPath($pbpath['parent']);
              $bundle_of_path = $group['bundle'];
            }

            // get the group-object for the current bundle we're on
            $groups = $pb->getGroupsForBundle($this->id());
                    
            // if there are several groups, for now take only the first one
            $group = current($groups);
          
            if(empty($group)) {
              // There should not be any error message here, this is a normal case
              //drupal_set_message(t("Bundle %b is associated with no groups", array('%b' => $this->id)));
              continue;
            }
#            dpm(microtime(), "normal handling");          
            // if the bundle and this object are not the same, the eid is the one of the
            // main bundle and the paths have to be absolute. In this case
            // we have to call it with false. 
            if($bundle_of_path != $this->id()) {
              // if this bundle is not the bundle where the path is in, we go to
              // absolute mode and give the length of the group because we find 
              // $eid there.
              $new_values = $adapter->getEngine()->pathToReturnValue($path, $pb, $eid, count($group->getPathArray())-1, NULL, FALSE); 
            } else // if not they are relative.
              $new_values = $adapter->getEngine()->pathToReturnValue($path, $pb, $eid, 0, NULL, TRUE);
            
#            dpm(microtime(), "new values");
            if (WISSKI_DEVEL) \Drupal::logger($pb_id.' '.$path_id.' '.__FUNCTION__)->debug('Entity '.$eid."{out}",array('out'=>serialize($new_values)));
          }
        }  
        if (empty($new_values)) {
          //dpm('don\'t have values for '.$path_id.' in '.$pb_id,$adapter->id());
        } else $values += $new_values;
      } //else dpm('don\'t know path '.$path_id,$pb_id);
    }
#    dpm($values);
    return $values;
  }
  
  public static function defaultPathOptions() {
    
    return array(
      'eid' => t('Entity\'s Drupal ID'),
      'uri.long' => t('Full URI'),
      'uri.short' => t('Short URI'),
      'bundle_label' => t('The bundle\'s label'),
      'bid' => t('The bundle\'s ID'),
    );    
  }
  
  public function getPathOptions() {
    
    $options = &$this->path_options;
    //if we already gathered the data, we can stop here
    if (empty($options)) {
      $options = self::defaultPathOptions();
      
      $moduleHandler = \Drupal::service('module_handler');
      if (!$moduleHandler->moduleExists('wisski_pathbuilder')){
        return NULL;
      }
                        
      
      //find all paths from all active pathbuilders
      $pbs = \Drupal::entityManager()->getStorage('wisski_pathbuilder')->loadMultiple();
#      $paths = array();
      foreach ($pbs as $pb_id => $pb) {
        #$paths = $pb->getAllPathsForBundleId($this->id(), TRUE);
        $paths = $pb->getAllPathsAndGroupsForBundleId($this->id(), TRUE);
        
#        dpm($paths);
        
        while($path = array_shift($paths)) {
          
          // see what is in the pathbuilder exactly
          $pbp = $pb->getPbPath($path->id());
          
          // if it is empty or it is disabled, continue
          if(empty($pbp) || !$pbp['enabled'])
            continue;
          
#          dpm($paths);
          if($path->isGroup()) {
            $options[$pb_id][$pb_id.'.'.$path->id()] = $path->getName() . ' (group label)';

            // in case of groups, get the subpaths and add them to the path... we continue there
            // directly to have the paths in the correct order
            $paths = array_merge($pb->getAllPathsAndGroupsForBundleId($pbp['bundle'], TRUE), $paths);
#            dpm($pb->getPbPath($path->id()), "yay!");
            #dpm($pb->getAllPathsAndGroupsForBundleId($path->id(), TRUE));
            
          }
          else
            $options[$pb_id][$pb_id.'.'.$path->id()] = $path->getName();
        }
/*
        $pb_paths = $pb->getAllPaths();
        foreach ($pb_paths as $path) {
          $path_id = $path->getID();
          if ($this->id() === $pb->getBundle($path_id)) {
            $options[$pb_id][$pb_id.'.'.$path_id] = $path->getName();
          } 
        }
*/
      }
    }
    return $options;
  }

  public function getUriString($entity_id,$type) {
    
    $uris = \Drupal\wisski_salz\AdapterHelper::getUrisForDrupalId($entity_id);
    if (empty($uris)) return '';
    $uri = current($uris);
    if ($type === 'uri.long') return $uri;
    if ($type === 'uri.short') {
      $matches = array();
      if (preg_match('/^.*[\#\/](.+)$/',$uri,$matches)) {
        return $matches[1];
      } else {
        drupal_set_message("no match for URI $uri", 'error');
      }
    }
    return '';
  }

  /**
   * Flushes the cache of generated entity titles
   * @param $entity_ids an array of IDs of entities whose titles shall be removed from this bundle's cache list, if NULL, all titles will be deleted
   */
  public function flushTitleCache($entity_ids = NULL) {

    if (is_null($entity_ids)) {
      unset($this->cached_titles);
      WisskiCacheHelper::flushAllEntityTitles($this->id());
    } elseif (!empty($entity_ids)) {
      foreach ((array) $entity_ids as $entity_id) {
        unset($this->cached_titles[$entity_id]);
        WisskiCacheHelper::flushEntityTitle($entity_id,$this->id());
      } 
    }
  }

  private function setCachedTitle($entity_id,$title) {
    
    $this->cached_titles[$entity_id] = $title;
    WisskiCacheHelper::putEntityTitle($entity_id,$title,$this->id());
  }

  public function getCachedTitle($entity_id) {
#    dpm(microtime(), "got cached title!");
    if (!isset($this->cached_titles[$entity_id])) {  
      if ($title = WisskiCacheHelper::getEntityTitle($entity_id,$this->id())) $this->cached_titles[$entity_id] = $title;
      else return NULL;
    }//dpm($this->cached_titles,'cached titles');
#    dpm(microtime(), "delivered.");
    return $this->cached_titles[$entity_id];
  }
  
  public function setTitlePattern($title_pattern) {
    $input = serialize($title_pattern);
    if ($input !== $this->title_pattern) {
      $this->title_pattern = $input;
      $this->flushTitleCache(); 
    }
    
#    $config = \Drupal::configFactory()->getEditable('wisski_core.wisski_bundle_title');
#    $config->set($this->id, $title_pattern)->save();
    $state = \Drupal::state()->get('wisski_core_title_patterns') ?: serialize(array());
    $state = unserialize($state);
    $state[$this->id] = $title_pattern;
    $state = serialize($state);
    \Drupal::state()->set('wisski_core_title_patterns', $state);
  }

  public function onEmpty() {
    
    return $this->on_empty;
  }
  
  public function setOnEmpty($type) {
    
    $type = intval($type);
    if ($type == self::DEFAULT_PATTERN || $type == self::FALLBACK_TITLE || $type == self::DONT_SHOW) {
      $this->on_empty = $type;
    } else drupal_set_message('Invalid fallback type for title pattern');
  }
  
  public function getFallbackTitle() {
    
    return $this->fallback_title;
  }
  
  public function setFallbackTitle($fallback_title) {
    
    if (is_string($fallback_title) && !empty($fallback_title))
      $this->fallback_title = $fallback_title;
  }

  public function getPagerLimit() {
    return $this->pager_limit;
  }
  
  public function setPagerLimit($limit) {
    $this->pager_limit = $limit;
  }
  
  public function getParentBundleIds($get_labels=TRUE) {
    
    $moduleHandler = \Drupal::service('module_handler');
    if (!$moduleHandler->moduleExists('wisski_pathbuilder')){
      return NULL;
    }
                      
    
    $pbs = \Drupal::entityManager()->getStorage('wisski_pathbuilder')->loadMultiple();
    $parents = array();
    foreach ($pbs as $pb_id => $pb) {
      $parent_id = $pb->getParentBundleId($this->id());
      if ($parent_id) {
        if ($get_labels) {
          $parent = self::load($parent_id);
          if (!empty($parent)) {
            $parents[$parent_id] = $parent->label();
          }
        } else $parents[$parent_id] = $parent_id;
      }
    }
    return $parents;
  }

  public function deleteBundleFromMenu($menu_name,  $destination_route = "entity.wisski_bundle.entity_list", $parameters = array() ) {

    if(empty($parameters))
      $parameters = array("wisski_bundle" => $this->id());
  
    $link = \Drupal\Core\Link::createFromRoute($this->label(), $destination_route, $parameters);
    
    // generate the parameter-string for the menu_link_content table
    $params = "";
    foreach($parameters as $key => $parameter) {
      $params .= $key . '=' . $parameter. ';';
    }

    // kill the last ; in the end
    $params = substr($params, 0, -1);

#    dpm('route:' . $destination_route . ';' . $params);

    // get the matching entities     
    $entities = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['menu_name' => $menu_name, 'title' => $this->label(), 'link__uri' => 'route:' . $destination_route . ';' . $params ]);

    // typically there should be only one.
    $entity = current($entities);
    
    if(!empty($entity))
      $entity->delete();
    
    return $entity;
  
  }

  /**
   * Creates a view for navigation
   *
   */   
  public function addViewForBundle($menu_name, $weight, $enabled) {
    $bundleid = $this->id();
    $bundle_name = $this->label();
    
    $options = array();
    $options['base_table'] = "wisski_individual";
    $options['id'] = $bundleid;
    $options['label'] = $bundle_name;
    $options['module'] = "views";
    $options['core'] = "8.x";
    $options['base_field'] = "eid";
    $options['originalId'] = $bundleid;

    $options['display']['default'] = array(
      'display_plugin' => 'default',
      'id' => 'default',
      'display_title' => 'Master',
      'position' => 0,
      'display_options' => array(
        'access' => array('type' => 'perm', 'options' => array( 'perm' => 'view any wisski content') ),
        'cache' => array('type' => 'none'), #array('type' => 'tag', 'options' => array() ),
        'query' => array('type' => 'views_query', 'options' => array() ),
        'exposed_form' => array('type' => 'basic', 'options' => array(
          'submit_button' => 'Apply',
          'reset_button' => false,
          'reset_button_label' => 'Reset',
          'exposed_sorts_label' => 'Sort by',
          'expose_sort_order' => true,
          'sort_asc_label' => 'Asc',
          'sort_desc_label' => 'Desc',
          ),
        ),
       'pager' => array('type' => 'full', 'options' => array(
         'items_per_page' => 24,
         'offset' => 0,
         'id' => 0,
         'total_pages' => NULL,
         'tags' => array('previous' => '<<', 'next' => '>>', 'first' => '<< First', 'last' => 'Last >>'),
         'expose' => array(
           'items_per_page' => false,
           'items_per_page_label' => 'Items per page',
           'items_per_page_options' => '5, 10, 25, 50',
           'items_per_page_options_all' => false,
           'items_per_page_options_all_label' => '- All -',
           'offset' => false,
           'offset_label' => 'Offset',
         ),
         'quantity' => 9,
         ),
       ),
       'style' => array(
         'type' => 'grid',
         'options' => array(
           'grouping' => array(),
           'columns' => 6,
           'automatic_width' => true,
           'alignment' => 'horizontal',
           'col_class_default' => true,
           'col_class_custom' => '',
           'row_class_default' => true,
           'row_class_custom' => '',
         ),
       ),
       'row' => array(
         'type' => 'fields',
       ),
       'fields' => array(
         'eid' => array(
           'id' => 'eid',
           'table' => 'wisski_individual',
           'field' => 'eid',
           'relationship' => 'none',
           'group_type' => 'group',
           'admin_label' => '',
           'label' => '',
           'exclude' => true,
           'alter' => 
          array(
             'alter_text' => false,
             'text' => '',
             'make_link' => false,
             'path' => '',
             'absolute' => false,
             'external' => false,
             'replace_spaces' => false,
             'path_case' => 'none',
             'trim_whitespace' => false,
             'alt' => '',
             'rel' => '',
             'link_class' => '',
             'prefix' => '',
             'suffix' => '',
             'target' => '',
             'nl2br' => false,
             'max_length' => 0,
             'word_boundary' => true,
             'ellipsis' => true,
             'more_link' => false,
             'more_link_text' => '',
             'more_link_path' => '',
             'strip_tags' => false,
             'trim' => false,
             'preserve_tags' => '',
             'html' => false,
          ),
           'element_type' => '',
           'element_class' => '',
           'element_label_type' => '',
           'element_label_class' => '',
           'element_label_colon' => false,
           'element_wrapper_type' => '',
           'element_wrapper_class' => '',
           'element_default_classes' => true,
           'empty' => '',
           'hide_empty' => false,
           'empty_zero' => false,
           'hide_alter_empty' => true,
           'entity_type' => 'wisski_individual',
           'plugin_id' => 'standard',
        ),
         'preview_image' => 
        array(
           'id' => 'preview_image',
           'table' => 'wisski_individual',
           'field' => 'preview_image',
           'relationship' => 'none',
           'group_type' => 'group',
           'admin_label' => '',
           'label' => '',
           'exclude' => false,
           'alter' => 
          array(
             'alter_text' => false,
             'text' => '',
             'make_link' => false,
             'path' => '',
             'absolute' => false,
             'external' => false,
             'replace_spaces' => false,
             'path_case' => 'none',
             'trim_whitespace' => false,
             'alt' => '',
             'rel' => '',
             'link_class' => '',
             'prefix' => '',
             'suffix' => '',
             'target' => '',
             'nl2br' => false,
             'max_length' => 0,
             'word_boundary' => true,
             'ellipsis' => true,
             'more_link' => false,
             'more_link_text' => '',
             'more_link_path' => '',
             'strip_tags' => false,
             'trim' => false,
             'preserve_tags' => '',
             'html' => false,
          ),
           'element_type' => '',
           'element_class' => '',
           'element_label_type' => '',
           'element_label_class' => '',
           'element_label_colon' => false,
           'element_wrapper_type' => '',
           'element_wrapper_class' => '',
           'element_default_classes' => true,
           'empty' => '',
           'hide_empty' => false,
           'empty_zero' => false,
           'hide_alter_empty' => true,
           'click_sort_column' => 'target_id',
           'type' => 'image_url',
           'settings' => 
          array(
             'image_style' => 'medium',
          ),
           'group_column' => '',
           'group_columns' => 
          array(
          ),
           'group_rows' => true,
           'delta_limit' => 0,
           'delta_offset' => 0,
           'delta_reversed' => false,
           'delta_first_last' => false,
           'multi_type' => 'separator',
           'separator' => ', ',
           'field_api_classes' => false,
           'entity_type' => 'wisski_individual',
           'plugin_id' => 'field',
        ),
         'title' => 
        array(
           'id' => 'title',
           'table' => 'wisski_individual',
           'field' => 'title',
           'relationship' => 'none',
           'group_type' => 'group',
           'admin_label' => '',
           'label' => '',
           'exclude' => false,
           'alter' => 
          array(
             'alter_text' => false,
             'text' => '',
             'make_link' => true,
//             'path' => '/wisski/navigate/{{eid}}/view?wisski_bundle=' . $bundleid,
             'absolute' => false,
             'external' => false,
             'replace_spaces' => false,
             'path_case' => 'none',
             'trim_whitespace' => false,
             'alt' => '',
             'rel' => '',
             'link_class' => '',
             'prefix' => '',
             'suffix' => '',
             'target' => '',
             'nl2br' => false,
             'max_length' => 0,
             'word_boundary' => true,
             'ellipsis' => true,
             'more_link' => false,
             'more_link_text' => '',
             'more_link_path' => '',
             'strip_tags' => false,
             'trim' => false,
             'preserve_tags' => '',
             'html' => false,
          ),
           'element_type' => '',
           'element_class' => '',
           'element_label_type' => '',
           'element_label_class' => '',
           'element_label_colon' => false,
           'element_wrapper_type' => '',
           'element_wrapper_class' => '',
           'element_default_classes' => true,
           'empty' => '',
           'hide_empty' => false,
           'empty_zero' => false,
           'hide_alter_empty' => true,
           'entity_type' => 'wisski_individual',
           'plugin_id' => 'standard',
        ),
      ),
       'filters' => 
      array(
         'bundle' => 
        array(
           'id' => 'bundle',
           'table' => 'wisski_individual',
           'field' => 'bundle',
           'relationship' => 'none',
           'group_type' => 'group',
           'admin_label' => '',
           'operator' => 'IN',
           'value' => 
          array(
             $bundleid => $bundleid,
          ),
           'group' => 1,
           'exposed' => false,
           'expose' => 
          array(
             'operator_id' => '',
             'label' => '',
             'description' => '',
             'use_operator' => false,
             'operator' => '',
             'identifier' => '',
             'required' => false,
             'remember' => false,
             'multiple' => false,
             'remember_roles' => 
            array(
               'authenticated' => 'authenticated',
            ),
             'reduce' => false,
          ),
           'is_grouped' => false,
           'group_info' => 
          array(
             'label' => '',
             'description' => '',
             'identifier' => '',
             'optional' => true,
             'widget' => 'select',
             'multiple' => false,
             'remember' => false,
             'default_group' => 'All',
             'default_group_multiple' => 
            array(
            ),
             'group_items' => 
            array(
            ),
          ),
           'entity_type' => 'wisski_individual',
           'plugin_id' => 'wisski_bundle',
        ),
      ),
       'sorts' => array(),
       'title' => $bundle_name,
       'header' => array(
         'result' => array(
           'id' => 'result',
           'table' => 'views',
           'field' => 'result',
           'relationship' => 'none',
           'group_type' => 'group',
           'admin_label' => '',
           'empty' => false,
           'content' => 'Displaying @start - @end of @total',
           'plugin_id' => 'result',
         ),
       ),
       'footer' => array(),
       'empty' => array(),
       'relationships' => array(),
       'arguments' => array(),
       'display_extenders' => array(),
       'use_ajax' => FALSE,
    ),
     'cache_metadata' => 
    array(
       'max-age' => -1,
       'contexts' => 
      array (
        0 => 'languages:language_interface',
        1 => 'url.query_args',
      ),
       'tags' => 
      array(
      ),
    ),
   );

    $options['display'][$bundleid] = array(
      'display_plugin' => 'page',
      'id' => $bundleid,
      'display_title' => $bundle_name,
      'position' => 1,
      'display_options' => array(
        'display_extenders' => array(),
        'path' => 'wisski_views/' . $bundleid,
        'menu' => array(
          'type' => 'normal',
          'title' => $bundle_name,
          'description' => '',
          'expanded' => FALSE,
          'parent' => '',
          'weight' => $weight,
          'context' => '0',
          'menu_name' => $menu_name,
        ),
      ),
      'cache_metadata' => array(
        'max-age' => -1,
        'contexts' => array (
          0 => 'languages:language_interface',
          1 => 'url.query_args',
        ),
        'tags' => array(),
      ),
    );
   
    $view = new View($options, 'view');
    
    $view->enable();
      
    $view->save();
  }
  
  public function addMenuItem($menu_name, $weight, $enabled, $destination_route, $parameters) {
    $link = \Drupal\Core\Link::createFromRoute($this->label(), $destination_route, $parameters);
    
    $entity = MenuLinkContent::create(array(
      'link' => ['uri' => $link->getUrl()->toUriString()],
       #        'langcode' => $node->language()->getId(),
      ));

    $entity->enabled->value = $enabled;


#    dpm($entity, "bundle");
#    $entity->id = $name;
    $entity->title->value = trim($this->label());
#    $entity->description->value = trim($group->getDe);
    $entity->menu_name->value = $menu_name;
#    $entity->parent->value = $values['parent'];
    $entity->weight->value = isset($entity->weight->value) ? $entity->weight->value : $weight;
    $entity->save();
  }

  /**
   * Adds a menu entry for a bundle
   * e.g. to navigate, find or create
   */
  public function addBundleToMenu($menu_name, $destination_route = "entity.wisski_bundle.entity_list", $parameters = array() ) {
#    drupal_set_message("I should add " . $this->id() . " to $menu_name");    
    $menu_mode = $this->getCreateMenuItems($menu_name, self::MENU_CREATE);

    $weight = 0;
    $enabled = TRUE;
    $found_menu_item = FALSE;
    $found_view = FALSE;
    
    $set = \Drupal::configFactory()->getEditable('wisski_core.settings');
    $use_views = $set->get('wisski_use_views_for_navigate');
    
    // we can only use views for navigate
    if($use_views && $menu_name != 'navigate') {
      $use_views = FALSE;
    }

    if (!$menu_mode) {
      // the setting says that we should not create a menu item
      return;
    }


    // first: get the old weight
    if(empty($parameters))
      $parameters = array("wisski_bundle" => $this->id());
    
    // generate the parameter-string for the menu_link_content table
    $params = "";
    foreach($parameters as $key => $parameter) {
      $params .= $key . '=' . $parameter. ';';
    }

    // kill the last ; in the end
    $params = substr($params, 0, -1);

    // get the matching entities     
    $entities = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['menu_name' => $menu_name, 'title' => $this->label(), 'link__uri' => 'route:' . $destination_route . ';' . $params ]);
    
    if(!empty($entities)) {
      // typically there should be only one.
      $entity = current($entities);

      $found_menu_item = TRUE;

      if(!empty($entity)) {
        if(isset($entity->weight->value))
          $weight = $entity->weight->value;
        if(isset($entity->enabled->value))
          $enabled = $entity->enabled->value;      
      }
    }
    
    $view = NULL;
    
    // now we should have the values for the menu-part... lets see if there is a view?
    // only do this for navigate
    if($menu_name == "navigate") {
      $view = \Drupal::service('entity.manager')->getStorage('view')->load($this->id());
    
      if(!empty($view)) {
        $display = $view->getDisplay($this->id());
        if(isset($display['display_options']['menu']['weight']))
          $weight = $display['display_options']['menu']['weight'];
        if(isset($display['display_options']['menu']['enabled']))
          $enabled =  $display['display_options']['menu']['enabled'];
        $found_view = TRUE;
      }
    }
     
    // if we didn't find a view or a menu we can assume pb-input as truth
    if(!$found_view && !$found_menu_item) {
   
#      $weight = 0;
      
      $moduleHandler = \Drupal::service('module_handler');
      if (!$moduleHandler->moduleExists('wisski_pathbuilder')){
        return NULL;
      }
                        
      
      // only act if there is no entity. Otherwise we can just check if everything is ok.
      $pbs = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple();
    
      $groups = array();
      //we ask all pathbuilders if they know the bundle
      foreach ($pbs as $pb_id => $pb) {
        $groups = array_merge($groups, $pb->getGroupsForBundle($this->id()));
    
        if(!empty($groups)) // we take the first one for now
          $group = current($groups);
        
        if(!empty($group)) {
          $pbp = $pb->getPbPath($group->id());
          $weight = $pbp['weight'];
          break;
        }    
      }
    }
 
#    drupal_set_message("I should add " . $this->id() . " to $menu_name and v:$found_view and m:$found_menu_item and uv:$use_views with weight:$weight and enabled " . serialize($enabled));
    
    // if there is a view and we are in menu create mode, we delete the view and create the menu.
    if($found_view && !$use_views && !empty($view)) {
      $view->delete();
      #$this->addViewForBundle($menu_name, $weight, $enabled);
    }
    
    // if there is a menu but we want to use views
    if($found_menu_item && $use_views) {
      $this->deleteBundleFromMenu($menu_name,  $destination_route, $parameters);
    }
    
    // if we want to use views and we didn't find any - create it
    if(!$found_view && $use_views) {
      $this->addViewForBundle($menu_name, $weight, $enabled);
    }
    
    // we didn't find a menu item and we don't want to use views
    if(!$found_menu_item && !$use_views) {
      $this->addMenuItem($menu_name, $weight, $enabled, $destination_route, $parameters);
    }
    
        
      // for further usage: language coding... currently no support at this point
/*
    if ($entity->isTranslatable()) {
      if (!$entity->hasTranslation($node->language()->getId())) {
        $entity = $entity->addTranslation($node->language()->getId(), $entity->toArray());
      }
      else {
        $entity = $entity->getTranslation($node->language()->getId());
      }
    }
    */
    
  }

  
  /** For each of the menus associated with this bundle, returns information 
   * whether to create a menu item for this bundle and whether it should be 
   * enabled by default.
   *
   * @param menu_name restrict the return value to the info for this menu
   * @param filter filter the menus' info. Can be MENU_CREATE, MENU_ENABLE or a
            combination thereof
   * @return an array where the keys are menu ids and the values are 
   *         MENU_CREATE, MENU_ENABLE or a combination thereof. If menu_name is
   *         given, only the menu's info is returned; if the menu does not 
   *         exist or was filtered out, FALSE is returned.
   */
  public function getCreateMenuItems($menu_name = NULL, $filter = NULL) {
    $menus = $this->menu_items + self::getCreateMenuItemDefaults();
    if ($filter !== NULL) {
      $menus = array_filter($menus, function($v) use ($filter) {
        return $v & $filter;
      });
    }
    if ($menu_name !== NULL) {
      if (isset($menus[$menu_name])) {
        return $menus[$menu_name];
      }
      // either the menu name does not exist or it was filtered out
      return FALSE;
    }
    return $menus;
  }


  public function setCreateMenuItems($items) {
    $this->menu_items = $items;
  }

  public static function getCreateMenuItemDefaults() {
    // currently we always want to create items for every menu
    return array_fill_keys(array_keys(self::getWissKIMenus()), self::MENU_CREATE | self::MENU_ENABLE);
  }


}
