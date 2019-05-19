<?php

/**
* This file contains common functions for analysers
*/


/** Get a UUID 4
*
* @author Martin Scholz
*
*/
function _wisski_textanly_generate_id() {
  return wisski_get_uuid(4);
}


/** Determine overlap info of two ranges
* @return an array with following information:
* 0 => allen relation: < > = s si f fi o oi d di m mi
* 1 => # chars that overlap
* 2 => % of chars that overlap
*
* @author Martin Scholz
*
*/
function _wisski_textanly_calculate_overlap($s1, $e1, $s2, $e2) {
  
  if ($s1 > $e2) return array('>', 0, 0); 
  if ($s1 == $e2) return array('mi', 0, 0);
  if ($s2 == $e1) return array('m', 0, 0);
  if ($s2 > $e1) return array('<', 0, 0);
  $all_chars = 0.5 * (($e1 - $s1) + ($e2 - $s2));
  if ($s1 == $s2) {
    if ($e1 == $e2) return array('=', $e1 - $s1, 100);
    if ($e1 < $e2) return array('s', $e1 - $s1, ($e1 - $s1) / $all_chars);
    if ($e1 > $e2) return array('si', $e1 - $s1, ($e2 - $s2) / $all_chars);
  }
  if ($s1 < $s2) {
    if ($e1 == $e2) return array('fi', $e2 - $s2, ($e2 - $s2) / $all_chars);
    if ($e1 < $e2) return array('o', $e1 - $s2, ($e1 - $s2) / $all_chars);
    if ($e1 > $e2) return array('di', $e2 - $s2, ($e2 - $s2) / $all_chars);
  }
  if ($s1 > $s2) {
    if ($e1 == $e2) return array('f', $e1 - $s1, ($e1 - $s1) / $all_chars);
    if ($e1 < $e2) return array('d', $e1 - $s1, ($e1 - $s1) / $all_chars);
    if ($e1 > $e2) return array('oi', $e2 - $s1, ($e2 - $s1) / $all_chars);
  }

}



/** Compares 2 annotations
* 
* Order:
* 1) Approved before non-approved
* 2) By rank: higher before lower
* 3) By range: left before right
*
* @author Martin Scholz
*
*/
function _wisski_textanly_compare_annotations($a, $b) {

// approved anno always beats non-approved anno
  if (isset($a['approved']) && $a['approved']) {
    if (!isset($b['approved']) || !$b['approved']) return 1;
  } elseif (isset($b['approved']) && $b['approved']) {
    return -1;
  }

  // if both are not approved, the rank decides. (Also if both are approved, though this should not occur)
  if (isset($a['rank']) && isset($b['rank'])) {

    // the higher ranked anno is greater
    $c = $a['rank'] - $b['rank'];
    if ($c) return $c;
    // if rank is equal, the longer anno is greater
    $l = ($a['range'][1] - $a['range'][0]) - ($b['range'][1] - $b['range'][0]);
    if ($l) return $l;
    // if length is equal, the one that has an 'approved' key is greater (it surely comes from the editor, thus we prefer the 'old' anno)
    return (isset($b['approved'])) ? -1 : 1;

  } elseif (isset($a['rank'])) {

    // annotation without rank is considered higher, because:
    // no rank generally means that the annotation comes from outside
    return 1;

  } elseif (isset($a['rank'])) {
    
    return -1;

  } else {
    
    // both don't have ranks => consider equal
    return 0;

  }

}




/** Helper function to generate list of available ontology classes and pathbuilder groups
*
* Returns an array of two arrays: array($classes, $groups)
* Each array has as keys the ontology class URI in its prefixed version, the values are
* either only the prefixed URI ($classes) or a concatenation of the prefixed URI and all pathbuilder groups
* that have this class as starting point ($groups).
* 
* @author Martin Scholz
*/
function _wisski_textanly_get_classes_list() {

  module_load_include('inc', 'wisski_pathbuilder');
  $classes = array();
  $groups = array();
  foreach (wisski_store_getObj()->wisski_ARCAdapter_getAllConcepts() as $c) {
    $gids = wisski_pathbuilder_getGroupsForConcept(wisski_store_getObj()->wisski_ARCAdapter_delNamespace($c), FALSE);
    if (empty($gids)) {
      $classes[$c] = $c;
    } else {
      $label = array();
      foreach ($gids as $id) {
        $info = wisski_pathbuilder_getPathData($id);
        if ($info['group_id'] < 1 && !empty($info['name'])) $label[] = $info['name'];
      }
      if (!empty($label)) {
        $groups[$c] = $c . ' (' . join(' / ', $label) . ')';
      } else {
        $classes[$c] = $c;
      }
    } 
  }

  return array($classes, $groups);

}




/*
*
* @author Martin Scholz
*
*/
function _wisski_textanly_get_tokens_for_anno(&$text_struct, $anno) {
  
  if (!isset($text_struct['tokens']) || empty($text_struct['tokens'])) return NULL;
  
  if (is_int($anno)) {
    if (!isset($text_struct['annos']) || empty($text_struct['annos'])) return NULL;
    $range = $text_struct['annos'][$anno]['range'];
  } elseif (is_array($anno) && isset($anno['range'])) {
    $range = $anno['range'];
  } elseif (is_array($anno) && count($anno) == 2) {
    $range = $anno;
  } else {
    return NULL;
  }
  
  $l = count($text_struct['tokens']);
  
  $s = $e = NULL;
  for ($j = 0; $j < $l; $j++) {
    if ($textstruct['tokens'][$j][2] > $range[0]) {
      $s = $j;
      break;
    }
  }
  for (++$j; $j < $l; $j++) {
    if ($textstruct['tokens'][$j][2] >= $range[1]) {
      $e = $j;
      break;
    }
  }
  
  if ($s === NULL || $e === NULL) return NULL;
  return array($s, $e);

}

