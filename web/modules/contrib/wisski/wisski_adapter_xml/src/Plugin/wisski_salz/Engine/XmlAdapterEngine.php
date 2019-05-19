<?php

/**
 * @file
 * Contains \Drupal\wisski_adapter_xml\Plugin\wisski_salz\Engine\XmlAdapterEngine
 */

namespace Drupal\wisski_adapter_xml\Plugin\wisski_salz\Engine;

use Drupal\wisski_adapter_xml\XmlAdapterBase;

use Drupal\wisski_pathbuilder\PathbuilderEngineInterface;

#use Symfony\Component\Xml\Xml;

use Drupal\Core\Language\LanguageInterface;

use Drupal\Component\Utility\NestedArray;

/**
 * @Engine(
 *   id = "wisski_adapter_xml",
 *   name = @Translation("XML Adapter"),
 *   description = @Translation("A WissKI adapter that parses a XML-string for entity info")
 * )
 */
class XmlAdapterEngine extends XmlAdapterBase implements PathbuilderEngineInterface {

  private $entity_info;

  private function getEntityInfo($forID=NULL,$cached = TRUE) {

    if (!$cached || !isset($this->entity_info) || (isset($forID) && !isset($this->entity_info[$forID]))) {
      $xml   = $this->xml2array($this->entity_string);
      $this->entity_info = $xml;
#      $this->entity_info = Xml::parse($this->entity_string);
    }
    return $this->entity_info;
  }
  
  public function getBundleIdsForEntityId($entity_id) {
    $entity_info = $this->load($entity_id);
    if (isset($entity_info['bundle'])) return array($entity_info['bundle']);
    return FALSE;
  }

  public function load($id) {
    $entity_info = $this->getEntityInfo($id);
    if (isset($entity_info[$id])) return $entity_info[$id];
    return array();
  }
  
  public function loadMultiple($ids = NULL) {
    $entity_info = $this->getEntityInfo(NULL,FALSE);
    if (is_null($ids)) return $entity_info;
    return array_intersect_key($entity_info,array_flip($ids));
  }
    
  /**
   * @inheritdoc
   */
  public function hasEntity($entity_id) {
  
    $ent = $this->load($entity_id);
    return !empty($ent);
  }
  
  public function getPrimitiveMapping($step) {
#    dpm($step, "primitive");
    return array("XML-Value");
  }


  /**
   * @inheritdoc
   */
  public function loadFieldValues(array $entity_ids = NULL, array $field_ids = NULL, $bundle=NULL,$language = LanguageInterface::LANGCODE_DEFAULT) {

    if (is_null($entity_ids)) {
      $ents = $this->loadMultiple();
      if (is_null($field_ids)) return $ents;
      $field_ids = array_flip($field_ids);
      return array_map(function($array) use ($field_ids) {return array_intersect_key($array,$field_ids);},$ents);
    }
    $result = array();
    foreach ($entity_ids as $entity_id) {
      $ent = $this->load($entity_id);
      if (!is_null($field_ids)) {
        $ent = array_intersect_key($ent,array_flip($field_ids));
      }
      $result[$entity_id] = $ent;
    }
    return $result;
  }

  /**
   * @inheritdoc
   * The Xml-Adapter cannot handle field properties, we insist on field values being the main property
   */
  public function loadPropertyValuesForField($field_id, array $property_ids, array $entity_ids = NULL, $bundle=NULL,$language = LanguageInterface::LANGCODE_DEFAULT) {
        
    $main_property = \Drupal\field\Entity\FieldStorageConfig::loadByName($entity_type, $field_name)->getItemDefinition()->mainPropertyName();
    if (in_array($main_property,$property_ids)) {
      return $this->loadFieldValues($entity_ids,array($field_id),$language);
    }
    return array();
  }
  
  /**
   * Returns a list of possible steps between history of steps and future of
   * steps.
   *
   * @param history An array of the previous steps or an empty array if this is
   *  the beginning of the path.
   * @param future An array of the following steps or an empty array if this is 
   *  (currently!) the last step.
   *
   * @return array
   *  A list of steps
   *
   */
  public function getPathAlternatives($history = [], $future = []) {
  
    $entity_info = $this->getEntityInfo();
    $keys = array();
    // @todo: this is clumsy....
    $pos = count($history);

    $sub_array = NestedArray::getValue($entity_info,$history);
    $sub_keys = array();

    foreach($sub_array as $sub_key => $sub_sub) {
      $sub_keys[$sub_key] = $sub_key;
#        dpm($sub_key, "sk");
#        dpm($sub_sub, "ss");
    }
#    dpm($sub_array, "sub");
# }
      
    $keys = array_merge($keys,$sub_keys);
/*
      if (is_array($sub_array)) {
        $sub_keys = array();
        foreach($sub_array as $sub_key => $sub_sub) {
        dpm($sub_key, "sk");
          if (empty($future) || NestedArray::keyExists($sub_sub,$future)) {
            $sub_keys[$sub_key] = $sub_key;
          }
        }
        $keys = array_merge($keys,$sub_keys);
      }
*/
#    }

//    dpm(func_get_args()+array('alternatives'=>$keys),__METHOD__);
    return $keys;
  }
  
  /**
   * Returns human readable information for a step.
   *
   * @param step the step
   *
   * @return an array with following keys
   *  label : a human readable label, translatable, one line
   *  description : a description of the step, translatable, multiline
   */
  public function getStepInfo($step, $history = [], $future = []) {
    dpm(func_get_args(),__METHOD__);
    return array();
  }

  public function checkUriExists($uri) {
    return TRUE;
  }
  
  public function getDrupalIdForUri($uri, $adapter_id = NULL) {
    return TRUE;
  }
  
  public function getUrisForDrupalId($uris) {
    return TRUE;
  }
  
  public function getSameUris($uri) {
    return TRUE;
  }
  
  public function getSameUri($uri, $adapter_id) {
    return TRUE;
  }
  
  public function setSameUris($uris, $entity_id) {
    return TRUE;
  }
  
  public function generateFreshIndividualUri() {
    return TRUE;
  }
  
  public function createEntity($entity) {
    return TRUE;
  }
  
  public function deleteEntity($entity) {
    return TRUE;
  }
  
    public function defaultSameAsProperties() {
    return array();
  }
  
  public function getNamespaces() {
    return array();
  }
  
  public function providesDatatypeProperty() {
    return TRUE;
  }

  function xml2array($url, $get_attributes = 1, $priority = 'tag', $is_file = FALSE)
  {
    $contents = "";
    if (!function_exists('xml_parser_create'))
    {
        return array ();
    }
    $parser = xml_parser_create('');
    
    if($is_file) {
      if (!($fp = @ fopen($url, 'rb')))
      {
          return array ();
      }
      while (!feof($fp))
      {
          $contents .= fread($fp, 8192);
      }
      fclose($fp);
    } else {
      $contents = $url;
    }
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);
    if (!$xml_values)
        return; //Hmm...
    $xml_array = array ();
    $parents = array ();
    $opened_tags = array ();
    $arr = array ();
    $current = & $xml_array;
    $repeated_tag_index = array ();
#    drupal_set_message(serialize($xml_values));
    foreach ($xml_values as $data)
    {
        unset ($attributes, $value);
        extract($data);
        $result = array ();
        $attributes_data = array ();
        if (isset ($value))
        {
            if ($priority == 'tag')
                $result = $value;
            else
                $result['value'] = $value;
        }
        if (isset ($attributes) and $get_attributes)
        {
            foreach ($attributes as $attr => $val)
            {
                if ($priority == 'tag')
                    $attributes_data[$attr] = $val;
                else
                    $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
            }
        }
        if ($type == "open")
        {
            $parent[$level -1] = & $current;
            
            if (!is_array($current) or (!in_array($tag, array_keys($current))))
            {
/*
                $current[$tag] = $result;
                if ($attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                $current = & $current[$tag];
*/
//              drupal_set_message(serialize($current));
//              drupal_set_message(serialize($tag));
              
              $current[$tag][0] = $result;
              $repeated_tag_index[$tag . '_' . $level] = 1;
              if ($attributes_data)
                $current[$tag]['0_attr'] = $attributes_data;
              $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
              $current = & $current[$tag][$last_item_index];
                                              
            }
            else
            {
                if (isset ($current[$tag][0]))
                {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else
                {
                    $current[$tag] = array (
                        $current[$tag],
                        $result
                    );
                    $repeated_tag_index[$tag . '_' . $level] = 2;
                    if (isset ($current[$tag . '_attr']))
                    {
                        $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                        unset ($current[$tag . '_attr']);
                    }
                }
                $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                $current = & $current[$tag][$last_item_index];
            }
        }
        elseif ($type == "complete")
        {
            if (!isset ($current[$tag]))
            {
                $current[$tag] = $result;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                if ($priority == 'tag' and $attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
            }
            else
            {
                if (isset ($current[$tag][0]) and is_array($current[$tag]))
                {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    if ($priority == 'tag' and $get_attributes and $attributes_data)
                    {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else
                {
                    $current[$tag] = array (
                        $current[$tag],
                        $result
                    );
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $get_attributes)
                    {
                        if (isset ($current[$tag . '_attr']))
                        {
//                            drupal_set_message(serialize($current));
//                            drupal_set_message(serialize($tag));
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset ($current[$tag . '_attr']);
                        }
                        if ($attributes_data)
                        {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                }
            }
        }
        elseif ($type == 'close')
        {
            $current = & $parent[$level -1];
        }
    }
    return ($xml_array);
  }
}