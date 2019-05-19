<?php

/**
 * @file
 * Contains \Drupal\wisski_triplify\Plugin\wisski_pipe\Processor\TriplifyStandard.
 */

namespace Drupal\wisski_triplify\Plugin\wisski_pipe\Processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\wisski_apus\AnnotationHelper;
use Drupal\wisski_pipe\ProcessorBase;
use Drupal\wisski_salz\AdapterHelper;
use Drupal\wisski_salz\Entity\Adapter;
use Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11Engine;
use DOMDocument;

/**
 * @Processor(
 *   id = "triplify_html_standard",
 *   label = @Translation("Triplify HTML"),
 *   description = @Translation("Generate triples from WissKI Annotated HTML."),
 *   tags = { "triplify", "html" }
 * )
 */
class TriplifyStandard extends ProcessorBase {
  
  protected $approved_only;

  protected $reference_properties;

  protected $write_adapter;


  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    // setConfiguration() sets default values on $this->configuration
    $this->approved_only = $this->configuration['approved_only'];
    $this->write_adapter = $this->configuration['write_adapter'];
    $this->reference_properties = $this->configuration['reference_properties'];
  }


  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $conf = array(
      'approved_only' => $this->approved_only,
      'write_adapter' => $this->write_adapter,
      'reference_properties' => $this->reference_properties,
    ) + parent::getConfiguration();
    return $conf;
  }

  
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $conf = array(
      'approved_only' => TRUE,
      'write_adapter' => NULL,
      'reference_properties' => array(),
    ) + parent::defaultConfiguration();
    return $conf;
  }

  
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    
    $adapters = Adapter::loadMultiple();
    $adapter_options = array("-" => $this->t("- None -"));
    foreach ($adapters as $adapter) {
      $engine = $adapter->getEngine();
      if ($engine instanceof Sparql11Engine) {
        $adapter_options[$adapter->id()] = $adapter->label();
      }
    }

    $form['#tree'] = TRUE;

    $form['write_adapter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Adapter to write to'),
      '#description' => $this->t('The adapter must be a sparql adapter'),
      '#default_value' => ($this->write_adapter === NULL) ? '-' : $this->write_adapter,
      '#options' => $adapter_options,
    );
    
    $form['approved_only'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Consider only approved annotations'),
      '#default_value' => $this->approved_only,
    );
    
    $form['reference_properties']['default_property']['property'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Reference property'),
      '#default_value' => (isset($this->reference_properties) && isset($this->reference_properties['default_property'])) ? $this->reference_properties['default_property']['property'] : '',
    );
    $form['reference_properties']['default_property']['inverse'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use property inversely'),
      '#default_value' => (isset($this->reference_properties) && isset($this->reference_properties['default_property'])) ? $this->reference_properties['default_property']['inverse'] : 0,
      '#description' => $this->t('If checked, the property will be interpreted inversely, ie. going from the referred instance to the document.'),
    );


/*

    $all_properties_opt = array('<none>' => t('<none>'));
    $res = db_query("SELECT `name` FROM {owl_property} WHERE `type` = 'owl:ObjectProperty'");
    while ($p = db_fetch_array($res)) {
      $name = wisski_store_getObj()->wisski_ARCAdapter_addNamespace($p['name']);
      $all_properties_opt[$p['name']] = $name;
    }
    asort($all_properties_opt);

    $model = wisski_textmod_get_model();

    foreach (array('subject' => t('Text subject'), 'reference' => t('Text reference')) as $reftype => $label) {

      $form[$reftype] = array(
        '#type' => 'fieldset',
        '#title' => $label,
        '#collapsible' => TRUE,
      );

      $text_groups = array();
      $ref_groups = array();
      foreach ($model['groups'] as $gid => $g) {
        $name = wisski_pathbuilder_getName($gid);
        if ($g['text_class'] !== NULL) $text_groups[$gid] = $name;
        if ($g['top']) $ref_groups[$gid] = $name;
      }
    
      foreach ($text_groups as $tg => $tg_name) {
        $tg_paths = $model['text_classes'][$model['groups'][$tg]['text_class']]['text_paths'];

        $form[$reftype]["text_group_$tg"] = array(
          '#type' => 'fieldset',
          '#title' => $tg_name,
          '#collapsible' => TRUE,
        );

        foreach ($ref_groups as $rg => $rg_name) {
          $rg_paths = $model['groups'][$rg]['foreign_paths'];
          $paths = array(0 => t('<None>'));
          foreach (array_intersect($tg_paths, $rg_paths) as $pid)
            $paths[$pid] = wisski_pathbuilder_getName($pid);
          
          $form[$reftype]["text_group_$tg"]["refered_group_$rg"] = array(
            '#type' => 'select',
            '#title' => t('of @g', array('@g' => $rg_name)),
            '#options' => $paths,
            '#default_value' => isset($settings[$reftype]["text_group_$tg"]["refered_group_$rg"]) ? $settings[$reftype]["text_group_$tg"]["refered_group_$rg"] : 0,
          );

        }

        $form[$reftype]["text_group_$tg"]['default_property']['property'] = array(
          '#type' => 'select',
          '#title' => t('Default Property'),
          '#options' => $all_properties_opt,
          '#default_value' => isset($settings[$reftype]["text_group_$tg"]['default_property']) ? $settings[$reftype]['default_property']['property'] : 'none',
          '#description' => t('The property will be interpreted as going from the referred instance to the text, ie. "text refers to instance".'),
        );
        $form[$reftype]["text_group_$tg"]['default_property']['inverse'] = array(
          '#type' => 'checkbox',
          '#title' => t('Use property inversely'),
          '#default_value' => isset($settings[$reftype]["text_group_$tg"]['default_property']) ? $settings[$reftype]['default_property']['inverse'] : 0,
          '#description' => t('If checked, the property will be interpreted inversely, ie. going from the text to the referred instance.'),
        );
      

      }
    
      $form[$reftype]['default_property'] = array(
        '#type' => 'fieldset',
        '#title' => t('Default property'),
      );
      $form[$reftype]['default_property']['property'] = array(
        '#type' => 'select',
        '#title' => t('Property'),
        '#options' => $all_properties_opt,
        '#default_value' => isset($settings[$reftype]['default_property']) ? $settings[$reftype]['default_property']['property'] : 'none',
        '#description' => t('The property will be interpreted as going from the referred instance to the text, ie. "text refers to instance".'),
      );
      $form[$reftype]['default_property']['inverse'] = array(
        '#type' => 'checkbox',
        '#title' => t('Use property inversely'),
        '#default_value' => isset($settings[$reftype]['default_property']) ? $settings[$reftype]['default_property']['inverse'] : 0,
        '#description' => t('If checked, the property will be interpreted inversely, ie. going from the text to the referred instance.'),
      );
    }
*/    
    
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->approved_only = $form_state->getValue('approved_only');
    
    $this->reference_properties = array();

    $ref_props = $form_state->getValue('reference_properties');
    if (isset($ref_props['default_property']['property']) && !empty($ref_props['default_property']['property'])) {
      $this->reference_properties['default_property'] = array(
        'property' => $ref_props['default_property']['property'],
        'inverse' => $ref_props['default_property']['inverse'],
      );
    }

    $this->write_adapter = $form_state->getValue('write_adapter');
    if ($this->write_adapter == '-') {
      $this->write_adapter = NULL;
    }

/*    // basically we can just return $values, but we have to remove empty values
    foreach ($values as $k1 => $v1) {
      if (is_array($v1)) {
        foreach ($v1 as $k2 => $v2) {
          if ($k2 == 'default_property') {
            if ($v2['property'] == '<none>') unset($values[$k1][$k2]);
          } else {
            foreach ($v2 as $k3 => $v3) {
              if ($k3 == 'default_property') {
                if ($v3['property'] == '<none>') unset($values[$k1][$k2][$k3]);
              } elseif ($v3 == 0) {
                unset($values[$k1][$k2][$k3]);
              }
            }
          }
        }
      }
    }
*/

  }

  
  /**
   * {@inheritdoc}
   */
  public function doRun() {
    
    if (isset($this->data['html'])) {
      $html_fragment = $this->data['html'];
    } elseif (isset($this->data['text'])) {
      $html_fragment = $this->data['text'];
    }
    if (empty($html_fragment)) {
      $this->data['triples'] = array();
      return;
    }
    $doc_inst = $this->data['document'];

    $xhtml = "<div>$html_fragment</div>"; // encapsulate: text may be xml/html fragment (leading/trailing chars or multiple root tags)
    $doc = new DOMDocument();
    $this->logDebug("Try to dom-parse fragment as xml: $xhtml");
    if (!$doc->loadXML($xhtml, LIBXML_NOERROR)) {
      $this->logDebug("Try to dom-parse fragment as html: $xhtml");
      if (!$doc->loadHTML('<?xml encoding="UTF-8">' . $xhtml)) {
        $this->logError($this->t('Could not parse text. No XML/HTML.'));
      }
    }
    $this->logDebug("Parsing complete");
    
    $anno_ids_with_elements = AnnotationHelper::getAnnotationIdsWithinElement($doc->documentElement);
#dpm($anno_ids_with_elements, 'ahh');
    $annos = array();
    foreach ($anno_ids_with_elements as $id => $elements) {
      $anno = new \stdClass();
      $anno->id = $id;
      $anno->body = new \stdClass();
      $anno->body->elements = $elements;
      $anno = AnnotationHelper::parseAnnotation($anno);
      $annos[$id] = $anno;
    }
#dpm($annos, 'ohh');    
    $triples = $this->triplifyAnnos($annos, $doc_inst);
    
    $graph_uri = $doc_inst;
    $this->data['triples'][$graph_uri] = $triples;
    
    if ($this->write_adapter !== NULL) {
      $adapter = entity_load('wisski_salz_adapter', $this->write_adapter);
      if ($graph_uri && $adapter) {
        $this->logInfo("Dropping text graph <{g}>", array('g' => $graph_uri));
        $delete_query = "DROP GRAPH <$graph_uri>";
dpm($delete_query);
#        $adapter->getEngine()->directUpdate($delete_query);
        $triple_str = join("\n  ", $triples);
        $this->logInfo("Inserting {c} triples into text graph <{g}>: {t}", array('g' => $graph_uri, 'c' => count($triples), 't' => $triple_str));
        $insert_query = "INSERT DATA { GRAPH <$graph_uri> {\n  $triple_str\n} }";
dpm($insert_query);        
#        $adapter->getEngine()->directUpdate($insert_query);
      }
    }

  }

  
  protected function triplifyAnnos($annos, $doc_inst) {
    
    // some shortcuts
    $ref_props = $this->reference_properties;

    $triples = array();
    
    foreach ($annos as $id => $anno) {
      
      if (!isset($anno->target) || empty($anno->target)) continue;

      if (!isset($anno->target->ref) || empty($anno->target->ref)) {
        // TODO: handle annos with unspecified target
        continue;
      }
      
      $clazz = isset($anno->target->type) ? $anno->target->type : NULL;
      
      $entity_infos = isset($anno->target->_entity_infos) ? $anno->target->_entity_infos : array();

      foreach ((array) $anno->target->ref as $ref_uri) {
        
#dpm($entity_infos, $ref_uri);
        if (isset($entity_infos[$ref_uri])) {
          $entity_id = $entity_infos[$ref_uri][0];
          $ref_uri = AdapterHelper::getUrisForDrupalId($entity_id, $this->write_adapter)[0];
if (!$ref_uri) dpm(array($entity_id, AdapterHelper::getUrisForDrupalId($entity_id, $this->write_adapter), AdapterHelper::getUrisForDrupalId($entity_id)),'lölö');
          // TODO: we could also get the bundle/class from [1] and use it as $clazz
        }

        // generate triple for the classification
        // if it is given explicitly
        if (!empty($clazz)) {
          $triples = "<$ref_uri> a <$clazz> .\n";
        }
 
        // generate triples for the reference statement
        // init the reference property to the default
        if (!empty($doc_inst)) {
          $ref_prop = $ref_props['default_property'];
          if (!empty($clazz) && isset($ref_props[$clazz]) && !empty($ref_props[$clazz]['property'])) {
            $ref_prop = $ref_props[$clazz];
          }
          if ($ref_prop['inverse']) {
            $triples[] = "<$ref_uri> " . $ref_prop['property'] . " <$doc_inst> .";
          } else {
            $triples[] = "<$doc_inst> " . $ref_prop['property'] . " <$ref_uri> .";
          }
        }

      }

    }

    return $triples;

  }


  protected function getTextContent($e) {
    
    $ws_replacements = array('br', 'p', 'div');
    $text = '';

    foreach ($e->childNodes as $c) {
      switch ($c->nodeType) {
        case XML_TEXT_NODE:
        case XML_CDATA_SECTION_NODE:
          $text .= $c->textContent;
          break;
        case XML_ELEMENT_NODE:
          $children_text = $this->getTextContent($c);
          if (in_array(strtolower($element->tagName), $ws_replacements)) $text .= " $children_text ";
          else $text .= $children_text;
          break;
      }
    }
    return preg_replace('/\s+/u', ' ', $text);  // normalize: all whitespace as single blank
  }





  function _wisski_texttrip_triplify_standard_walk($element, $doc_inst) {
    
    $triples = array();
    $uris_to_gids_and_rels = array();
    $agenda = array($element);

    while (!empty($agenda)) {
      $e = array_shift($agenda);
      
      // push child elements on agenda
      foreach ($e->childNodes as $c)
        if ($c->nodeType == XML_ELEMENT_NODE) $agenda[] = $c;
      
      // check if this element contains annotation info
      if ($e->hasAttribute('data-wisski-anno-id')) {
        
/* this should be of no hurt now        if (strpos($e->getAttribute('data-wisski-anno'), 'tabu') !== FALSE) continue; */
        
        $uri = NULL;
        $gid = -1;
        $voc = NULL;
        $subst_voc = NULL;
        $approved = FALSE;
        $rels = array();
        $revs = array();
        $subject = FALSE;

        foreach(explode(' ', $e->getAttribute('class')) as $cl) {
          if (substr($cl, 0, 18) == 'wisski_anno_class_') $gid = urldecode(substr($cl, 18));
          if (substr($cl, 0, 16) == 'wisski_anno_uri_') $uri = urldecode(substr($cl, 16));
          if ($vocab_ctrl_exists && substr($cl, 0, 18) == 'wisski_anno_vocab_') $voc = urldecode(substr($cl, 18));
          if (substr($cl, 0, 20) == 'wisski_anno_approved') $approved = TRUE;
          if (substr($cl, 0, 19) == 'wisski_anno_subject') $subject = TRUE;
          if (substr($cl, 0, 16) == 'wisski_anno_rel_') {
            $rel = explode(':', substr($cl, 16));
            $rels[urldecode($rel[0])][] = urldecode($rel[1]);
          }
  // curently we do not support reverse relations
  //        if (substr($cl, 0, 16) == 'wisski_anno_rev_') {
  //          $rel = explode(':', substr($cl, 16));
  //          $revs[urldecode($rel[0])][] = urldecode($rel[1]);
  //        }
        }

   //     if ($e->hasAttribute('about')) $uri = $e->getAttribute('about');
   //     if ($e->hasAttribute('typeof')) $class = $e->getAttribute('typeof');
       
        if (!$approved && $settings['approved_only']) continue;

        if (!$uri && $gid == -1) continue; // we can't make anything useful with that

        if (!$uri) $uri = wisski_texttrip_create_instance_uri($gid);
        
        if ($voc) { // can only be nonnull if vocab_ctrl module is enabled
          $voc = wisski_vocab_ctrl_get($voc);
        }
        if ($voc && $voc->group_id != !$gid) {
          $gid = $voc->group_id;
        }
        
        // check whether instance needs to be imported from voc
        // whether it needs to be created
        if ($voc) {
          $is_imported = wisski_vocab_ctrl_is_imported($voc->vid, $uri);
          if (!$is_imported) {
            $tr = wisski_vocab_ctrl_get_triples($voc->vid, $anno['uri']);
            $triples = array_merge($triples, $tr);
          }
        } else {
          $q = "ASK { <$uri> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?c . }";
          $store = wisski_store_getObj()->wisski_ARCAdapter_getStore();
          $is_imported = $store->query($q, 'raw');
          if ($store->getErrors()) {
            foreach ($store->getErrors() as $e) drupal_set_message("extracting triples: error asking TS: $e");
          }
          
          if (!$is_imported) {
            // as there is no voc, we search for a voc substitute
            // so that we can store the label

            foreach (wisski_vocab_ctrl_get() as $v) {
              if ($v->group_id == $gid && wisski_accesspoint_get($v->aid)->type == 'local') {
                $label = _wisski_texttrip_triplify_standard_get_content($e);
                $tr = _wisski_pathbuilder_generate_triples_of_path(NULL, $label, $v->fields['label']['cis_pid'], $uri);
                $triples = array_merge($triples, $tr);
                $is_imported = TRUE;
                break;
              }
            }
          }
          
          if (!$is_imported && $gid == -1) continue;
          
          if ($gid != -1) {
            // we add the class nevertheless so we can be sure that a node will generated
            $path = _wisski_pathbuilder_calculate_group_samepart($gid);
            $class = $path['x' . floor(count($path) / 2)];
            $triples[] = array(
              's' => $uri,
              's_type' => 'uri',
              'p' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
              'o' => $class,
              'o_type' => 'uri',
            );
          }

        }
        
        // add rels to uri; we need to store this and aterwards create relations
        if (!isset($uris_to_gids_and_rels[$uri]))
          $uris_to_gids_and_rels[$uri] = array('gid' => -1, 'rels' => array());
        foreach ($rels as $rel => $targets) { // cannot array_merge as keys get lost...
          if (isset($uris_to_gids_and_rels[$uri]['rels'][$rel])) {
            $uris_to_gids_and_rels[$uri]['rels'][$rel] = array_merge($uris_to_gids_and_rels[$uri]['rels'][$rel], $targets);
          } else {
            $uris_to_gids_and_rels[$uri]['rels'][$rel] = $targets;
          }
        }
        // we need to store which group an inst belongs to for creating relations
        if ($gid != -1 && $uris_to_gids_and_rels[$uri]['gid'] == -1)
          $uris_to_gids_and_rels[$uri]['gid'] = $gid;
        
        if ($subject) {
          $tr = _wisski_texttrip_triplify_standard_reference($settings['subject'], $uri, $gid, $text_inst, $text_inst_gid);
          if ($tr) $triples = array_merge($triples, $tr);
        }
        if (!$subject || $settings['reference_and_subject']) {
          $tr = _wisski_texttrip_triplify_standard_reference($settings['reference'], $uri, $gid, $text_inst, $text_inst_gid);
          if ($tr) $triples = array_merge($triples, $tr);
        }
        
        // we cannot triplify relations now as we need to collect info
        // about the rel targets which potentially comes later

      }
    } 
    

    // make relations
    foreach ($uris_to_gids_and_rels as $uri => $gid_and_rels) {
      
      $gid = $gid_and_rels['gid'];
      $rels = $gid_and_rels['rels'];

      $terminal_rels = array();
      $interm_rels = array();
      foreach ($rels as $r => $targets) {
        $rel_path_data = wisski_pathbuilder_getPathData($r);
        $terminal_rels[$r] = $targets;
        while ($rel_path_data['group_id'] != $gid && $rel_path_data['group_id'] > 0) {
          $rel_path_data = wisski_pathbuilder_getPathData($rel_path_data['group_id']);
          if (!in_array($rel_path_data['id'], $interm_rels)) $interm_rels[] = $rel_path_data['id'];
        }
      }
      array_unique($interm_rels);

      // make triples for subgroups and store uris of last instance in path as starting instance of subpaths/groups
      $group_sources = array($gid => $uri);
      foreach ($interm_rels as $rel) {
        $triples = array_merge($triples, _wisski_texttrip_triplify_standard_relation($group_sources, $rel));
      }

      foreach ($terminal_rels as $rel => $targets) {
        foreach ($targets as $target) {

          if (!isset($uris_to_gids_and_rels[$target])) continue;  // there is no annotation with the target uri

          if (isset($uris_to_gids_and_rels[$target]['gid'])) {
            $tgid = $uris_to_gids_and_rels[$target]['gid'];
          } else {
            continue; // cannot determine group of target
          }

          $triples = array_merge($triples, _wisski_texttrip_triplify_standard_relation($group_sources, $rel, $target, $tgid));
        }
      }
    
    }
    
    return array('triples' => $triples);

  }




  function _wisski_texttrip_triplify_standard_get_content($e) {
    
    $ws_replacements = array('br', 'p', '/p', 'div', '/div');
    $text = '';

    foreach ($e->childNodes as $c) {
      switch ($c->nodeType) {
        case XML_TEXT_NODE:
        case XML_CDATA_SECTION_NODE:
          $text .= $c->textContent;
          break;
        case XML_ELEMENT_NODE:
          $children_text = _wisski_texttrip_triplify_standard_get_content($c);
          if (in_array(strtolower($element->tagName), $ws_replacements)) $text .= " $children_text ";
          else $text .= $children_text;
          break;
      }
    }
    return preg_replace('/\s+/u', ' ', $text);  // normalize: all whitespace as single blank
  }


  function _wisski_texttrip_triplify_standard_reference($settings, $uri, $gid, $text_inst, $text_inst_gid) {
    
    if (isset($settings["text_group_$text_inst_gid"])) {
      if (isset($settings["text_group_$text_inst_gid"]["refered_group_$gid"])) {
        $s = $settings["text_group_$text_inst_gid"]["refered_group_$gid"];
        return _wisski_texttrip_triplify_standard_path_link($s['pid'], $uri, $s['inst_x'], $text_inst, $s['text_inst_x']);
      } elseif (isset($settings["text_group_$text_inst_gid"]['default_property'])) {
        $s = $settings["text_group_$text_inst_gid"]['default_property'];
      }
    }
    if (!$s && isset($settings['default_property'])) {
      $s = $settings['default_property'];
    }

    if ($s) {
      if (!$s['inverse']) {
        return _wisski_texttrip_triplify_standard_property_link($uri, $s['property'], $text_inst);
      } else {
        return _wisski_texttrip_triplify_standard_property_link($text_inst, $s['property'], $uri);
      }
    } else {
      return array();
    }
    
  }


  function _wisski_texttrip_triplify_standard_path_link($pid, $uri1, $uri1_x, $uri2, $uri2_x) {
    
    $samepart = ($uri1_x == 0) ? array() : array_fill(0, $uri1_x * 2, '');
    $samepart["x$uri1_x"] = $uri1;

    // 'a' is a dummy param
    return _wisski_pathbuilder_generate_triples_of_path(NULL, 'a', $pid, $uri1, $samepart, $uri2, $uri2_x);
    
  }


  function _wisski_texttrip_triplify_standard_property_link($uri1, $prop, $uri2) {
    
    return array(
      array(
        's' => $uri1,
        's_type' => 'uri',
        'p' => $prop,
        'o' => $uri2,
        'o_type' => 'uri',
      )
    );
    
  }



  /** Helper function for _wisski_texttrip_triplify_xhtml
  * Make triples for a relation between two annotations
  * This also generates instances in the paths
  *
  * @author Martin Scholz
  *
  */
  function _wisski_texttrip_triplify_standard_relation(&$group_sources, $rel, $target_uri = NULL, $target_class = NULL) {

    include_once(drupal_get_path('module', 'wisski_pathbuilder') . '/wisski_pathbuilder.inc');
    $path_data = wisski_pathbuilder_getPathData($rel);
    $source = $group_sources[$path_data['group_id']];

    if ($target_class) {
      $tmp1 = wisski_pathbuilder_getPathData($target_class);
      $tmp2 = _wisski_pathbuilder_calculate_group_samepart($target_class);
      if (!$tmp1['group_id']) {  // top group
        $target_class = $tmp2['x0']; // this should be needless, as samapart of top group should be only first concept
      } else {
        $target_class = $tmp2['x' . (floor(count($tmp2) / 2) + 1)];
      }
    }

    $triples = array();

    global $base_root, $base_path;
    $proto_uri = $base_root . $base_path . 'content/' . md5("$source $rel") . md5("$target $target_class") . "_";

    if ($path_data['is_group']) {
      $path_array = _wisski_pathbuilder_calculate_group_samepart($rel);
    } else {
      $path_array = unserialize($path_data['path_array']);
    }

    $group_path_array = _wisski_pathbuilder_calculate_group_samepart($path_data['group_id']);

    $start_step = floor(count($group_path_array) / 2);

    $i = $start_step + 1;
    $last_s = $source;

    while (isset($path_array["x$i"])) {

      if ($path_array["x$i"] == $target_class) {

        $triples[] = array(
            's' => $last_s,
            's_type' => 'uri',
            'p' => $path_array['y' . ($i-1)],
            'o' => $target_uri,
            'o_type' => 'uri',
            );
        break;

      } else {

        $next_s = "$proto_uri$i";

        $triples[] = array(
            's' => $last_s,
            's_type' => 'uri',
            'p' => $path_array['y' . ($i-1)],
            'o' => $next_s,
            'o_type' => 'uri',
            );
        $triples[] = array(
            's' => $next_s,
            's_type' => 'uri',
            'p' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
            'o' => $path_array["x$i"],
            'o_type' => 'uri',
            );

        $last_s = $next_s;
        $i++;

      }

      if ($target_uri == NULL) $group_sources[$rel] = $last_s;

    }

    return $triples;

  }


}






