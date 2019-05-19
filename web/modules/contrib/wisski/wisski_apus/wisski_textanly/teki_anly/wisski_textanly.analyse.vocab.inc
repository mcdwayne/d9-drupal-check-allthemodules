<?php

/* ***************** *\
*  ADMIN PAGES        *
\* ***************** */


/**
* @author Martin Scholz
*/
function wisski_textanly_admin_analyse_vocab($op, $ap) {

  switch ($op) {

    case 'edit':

      drupal_load('module', 'wisski_pathbuilder');
      include_once(drupal_get_path('module', 'wisski_pathbuilder') . '/wisski_pathbuilder.inc');

      //for pb_group: display every top group of the import PB section
      $pb_groups = array();
      foreach (wisski_pathbuilder_getEnabledGroups(3) as $id) {
        $info = wisski_pathbuilder_getPathData($id);
        if ($info['group_id'] < 1) $pb_groups["$id"] = $info['name'];
      }

      if (!$ap->settings) {
        $ap->settings = array(
          'place_classes' => array(),
          'place_use_approved_coordinates' => '0',
          'place_coords' => array(),
          'rank_offset_coords_lat' => '0',
          'rank_offset_coords_long' => '0',
          'rank_offset_exact' => '0',
          'rank_factor_length_contains' => '0',
          'rank_offset_contains' => '0',
          'rank_factor_length_guess' => '0',
          'rank_offset_guess' => '0',
          'rank_offset_has_lemma' => array(),
          'rank_offset_has_pos' => array(),
        );

        foreach ($pb_groups as $id => $grp) {
          $ap->settings['rank_offset_has_lemma'][$id] = "0";
          $ap->settings['rank_offset_has_pos'][$id] = array();
        }

      }

      $coords_str = '';
      foreach ($ap->settings['place_coords'] as $coords) {
        $coords_str .= $coords['lat'] . ' ' . $coords['long'] . "\n";
      }

      $items = array(
        'places' => array(
          '#type' => 'fieldset',
          '#title' => t('place coordinates check'),
          'place_classes' => array(
            '#type' => 'textfield',
            '#title' => t('place classes'),
            '#default_value' =>  join(' ', $ap->settings['place_classes']),
          ),
          'use_coordinates_approved' => array(
            '#type' => 'checkbox',
            '#title' => t('use coordinates of approved annotations'),
            '#default_value' => $ap->settings['place_use_approved_coordinates'],
          ),
          'place_coords' => array(
            '#type' => 'textarea',
            '#title' => t('preferred coords'),
            '#default_value' =>  $coords_str,
            '#description' => t('each line contains separated by space: latitude, longitude'),
          ),
          'place_lat_factor' => array(
            '#type' => 'textfield',
            '#title' => t('Latitude factor'),
            '#default_value' => $ap->settings['rank_offset_coords_lat'],
          ),
          'place_long_factor' => array(
            '#type' => 'textfield',
            '#title' => t('Longitude factor'),
            '#default_value' => $ap->settings['rank_offset_coords_long'],
          ),
        ),
        'rank_offset_exact' => array(
          '#type' => 'textfield',
          '#title' => t('Rank offset exact'),
          '#default_value' => $ap->settings['rank_offset_exact'],
        ),
        'rank_factor_length_contains' => array(
          '#type' => 'textfield',
          '#title' => t('Rank factor length contains'),
          '#default_value' => $ap->settings['rank_factor_length_contains'],
        ),
        'rank_offset_contains' => array(
          '#type' => 'textfield',
          '#title' => t('Rank offset contains'),
          '#default_value' => $ap->settings['rank_offset_contains'],
        ),
        'rank_factor_length_guess' => array(
          '#type' => 'textfield',
          '#title' => t('Rank factor length guess'),
          '#default_value' => $ap->settings['rank_factor_length_guess'],
        ),
        'rank_offset_guess' => array(
          '#type' => 'textfield',
          '#title' => t('Rank offset guess'),
          '#default_value' => $ap->settings['rank_offset_guess'],
        ),
        'has_lemma' => array(
          '#type' => 'fieldset',
          '#title' => t('has lemma'),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        ),
        'has_pos' => array(
          '#type' => 'fieldset',
          '#title' => t('has pos'),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        ),
      );

      foreach ($pb_groups as $id => $grp) {
        $items['has_lemma']['has_lemma_'.$id] = array(
            '#type' => 'textfield',
            '#title' => t($grp),
            '#default_value' => $ap->settings['rank_offset_has_lemma'][$id],
            );
      }

      foreach ($pb_groups as $id => $grp) {

        $str = "";
        if (isset($ap->settings['rank_offset_has_pos'][$id]) && !empty($ap->settings['rank_offset_has_pos'][$id])) {
          foreach ($ap->settings['rank_offset_has_pos'][$id] as $key => $val) {
            $tmp = "";
            if ($key == "") {
              $tmp = $val;
            } else {
              $tmp = $key." ".$val;
            }
            if ($str =="") {
              $str =$tmp;
            } else {
              $str = $str."\n".$val;
            }
          }
        }
        $items['has_pos']['has_pos_'.$id] = array(
            '#type' => 'textarea',
            '#title' => t($grp),
            '#default_value' => $str,
            );
      }

      return $items;

    case 'submit':
      drupal_load('module', 'wisski_pathbuilder');
      include_once(drupal_get_path('module', 'wisski_pathbuilder') . '/wisski_pathbuilder.inc');
      
      //for pb_group: display every top group of the import PB section
      $pb_groups = array();
      foreach (wisski_pathbuilder_getEnabledGroups(3) as $id) {
        $info = wisski_pathbuilder_getPathData($id);
        if ($info['group_id'] < 1) $pb_groups["$id"] = $info['name'];
      }

      //update vocab:
      $vocab_settings = array();
      $coords = array();
      foreach (explode("\n", $ap->settings['places']['place_coords']) as $cline) {
        $cline = preg_split("/ +/", $cline, -1, PREG_SPLIT_NO_EMPTY);
        if (count($cline) != 2) continue;
        $coords[] = array_combine(array('lat', 'long'), $cline);
      }

      $vocab_settings['place_coords'] = $coords;
      $vocab_settings['rank_offset_coords_lat'] = $ap->settings['places']['place_lat_factor'];
      $vocab_settings['rank_offset_coords_long'] = $ap->settings['places']['place_long_factor'];
      $vocab_settings['place_use_approved_coordinates'] = $ap->settings['places']['use_coordinates_approved'];
      $vocab_settings['place_classes'] = preg_split('/ +/', $ap->settings['places']['place_classes'], PREG_SPLIT_NO_EMPTY);
      $vocab_settings['rank_offset_exact'] = $ap->settings['rank_offset_exact'];

      $vocab_settings['rank_offset_exact'] = $ap->settings['rank_offset_exact'];
      $vocab_settings['rank_offset_contains'] = $ap->settings['rank_offset_contains'];
      $vocab_settings['rank_offset_guess'] = $ap->settings['rank_offset_guess'];
      $vocab_settings['rank_factor_length_contains'] = $ap->settings['rank_factor_length_contains'];
      $vocab_settings['rank_factor_length_guess'] = $ap->settings['rank_factor_length_guess'];

      $apArray = (array) $ap->settings;

      $vocab_settings['rank_offset_has_lemma'] = array();
      foreach ($pb_groups as $id => $grp) {
        $vocab_settings['rank_offset_has_lemma'][$id] = $apArray['has_lemma_'.$id];
      }

      $vocab_settings['rank_offset_has_pos'] = array();
      foreach ($pb_groups as $id => $grp) {
        $has_pos = wisski_textanly_build_has_pos_reverse($apArray['has_pos_'.$id]);
        $vocab_settings['rank_offset_has_pos'][$id] = $has_pos;
      }

      return $vocab_settings;

    case 'key_info':
      return array(
        'input' => array(
          'tokens',
          'lemmata',
          'pos',
          'annos',
        ),
        'output' => array(
          'annos',
        )
      );

    default:

      return NULL;

  }

}




/**
* @author  Florian Wittmann
*/
function wisski_textanly_build_has_pos_reverse($text) {
  $lines = preg_split('/\r\n|\r|\n/', $text);
  foreach ($lines as $i => $line) {
    $parts = preg_split("/[\s,]+/", $line, -1, PREG_SPLIT_NO_EMPTY);
    if (count($parts)==1) {
      $rankings[''] = $parts[0];
    } elseif (count($parts)==2) {
      $rankings[$parts[0]] = $parts[1];
    }
  }
  return $rankings;
}


/* ***************** *
*  ANALYSER
** ***************** */


/** Find occurences of vocabulary terms in text
* This is the analyse operation for analysis component type vocab
*
* @author Martin Scholz, Florian Wittmann
*
*/
function wisski_textanly_analyse_vocab($analyser, $text_struct, $ticket) {
  
  if (!isset($text_struct['annos'])) $text_struct['annos'] = array();
  $annos = $text_struct['annos'];
  $tokens = $text_struct['tokens'];
  
  if (empty($tokens)) return $text_struct;

  $settings = &$analyser->settings;
  if(!isset($settings['rank_offset_exact'])) $settings['rank_offset_exact'] = 1;
  if(!isset($settings['rank_factor_length_contains'])) $settings['rank_factor_length_contains'] = 5;
  if(!isset($settings['rank_offset_contains'])) $settings['rank_offset_contains'] = 1;
  if(!isset($settings['rank_factor_length_guess'])) $settings['rank_factor_length_guess'] = -2.4;
  if(!isset($settings['rank_offset_guess'])) $settings['rank_offset_guess'] = -1;
  if(!isset($settings['rank_offset_coords_lat'])) $settings['rank_offset_coords_lat'] = -1.5;
  if(!isset($settings['rank_offset_coords_long'])) $settings['rank_offset_coords_long'] = -2.4;

  if (isset($settings['place_use_approved_coordinates']) && $settings['place_use_approved_coordinates']) {
    foreach ($annos as $anno) {
      if (isset($anno['approved']) && $anno['approved'] &&
          isset($anno['uri']) && isset($anno['voc']) && in_array($anno['class'], $settings['place_classes'])) {
        $c = wisski_vocab_ctrl_lookup(array('latitude', 'longitude'), $anno['voc'], $anno['uri'], NULL);
        $settings['place_coords'][] = array('lat' => $c[$anno['voc']][$anno['uri']]['latitude'][0]['value'], 'long' => $c[$anno['voc']][$anno['uri']]['longitude'][0]['value']);
      }
    }
  }
  
  include_once(dirname(__FILE__) . '/wisski_textanly.analyse.tokenizer.inc');
  include_once(dirname(__FILE__) . '/wisski_textanly.analyse.common.inc');

  $tokens_len = count($tokens);
  for ($token_pos = 0; $token_pos < $tokens_len; $token_pos++) {

    $tmp = _wisski_textanly_analyse_vocab_token($tokens, $token_pos, $tokens_len, $annos, $settings, $text_struct, $ticket);

    $annos = array_merge($annos, $tmp);
  }
  
  $text_struct['annos'] = $annos;
  return $text_struct;

}


/* Helper function for wisski_textanly_analyse_vocab
* Analyse a single token
*
* @author Martin Scholz, Florian Wittmann
*
*/
function _wisski_textanly_analyse_vocab_token($tokens, $token_pos, $tokens_len, $annos, $settings, &$text_struct, $ticket) {
  $rank_offset_exact = $settings['rank_offset_exact'];
  $rank_factor_length_contains = $settings['rank_factor_length_contains'];
  $rank_offset_contains = $settings['rank_offset_contains'];
  $rank_factor_length_guess = $settings['rank_factor_length_guess'];
  $rank_offset_guess = $settings['rank_offset_guess'];
  $rank_offset_coords_lat = $settings['rank_offset_coords_lat'];
  $rank_offset_coords_long = $settings['rank_offset_coords_long'];
  $coords = $settings['place_coords'];
  $place_classes = $settings['place_classes'];
  $rank_offset_has_lemma = $settings['rank_offset_has_lemma'];
  $rank_offset_has_pos = $settings['rank_offset_has_pos'];

  $token = $tokens[$token_pos];
  $lemma = isset($text_struct['lemmata']) ? $text_struct['lemmata'][$token_pos] : '';

  // first perform an exact query, then a broader one
  $exact = array();
  $overlap = array();
  $guess = array();

  // this is the exact query; 
  $q  = "SELECT * FROM {wisski_vocab_ctrl_index} WHERE value = '%s'" . (empty($lemma) ? '' : " OR value = '%s'");
  $res = db_query($q, $token[0], $lemma);
  while ($row = db_fetch_object($res)) {

    $label = $row->value;
    if ($label != $token[0] && $label != $lemma) continue;
    $voc = wisski_vocab_ctrl_get($row->vid);


    $constraint_offset = _wisski_textanly_analyse_coord_offset($row, $voc, $settings);
    //check for lemma
    $constraint_offset += _wisski_textanly_analyse_lemma_offset($token, $lemma, $voc, $settings);
    //check for pos
    $constraint_offset += _wisski_textanly_analyse_pos_offset($token, isset($text_struct['pos']) ? $text_struct['pos'][$token_pos] : '', $voc, $settings);

    $exact[] = array(
        'id' => _wisski_textanly_generate_id(),
        'voc' => $voc->vid,
        'class' => (string) $voc->group_id,
        'uri' => $row->uri,
        'args' => array('label' => $label),
        'lang' => $row->lang,
        'range' => array($token[1], $token[2]),
        'rank' => $voc->priority + $rank_offset_exact + $constraint_offset,
        );

  }

  if ($exact) {
    usort($exact, function($a, $b) { $c = $b['rank'] - $a['rank']; return ($c == 0) ? 0 : ($c < 0) ? -1 : 1; });
    $exact = array(wisski_vocab_ctrl_get($exact[0]['voc'], 'priority') => array($exact[0]));
  }

  // this is the 'contains_word' match mode without exact matches
  $q  = "SELECT * FROM {wisski_vocab_ctrl_index} WHERE ";
  $q .= "value LIKE '%s %%' OR value LIKE '%s-%%'";
  if (!empty($lemma)) $q .= "OR value LIKE '%s %%' OR value LIKE '%s-%%'";
  $res = db_query($q, $token[0], $token[0], $lemma, $lemma);

  while ($row = db_fetch_object($res)) {
    
    $label = $row->value;
    $pos = stripos($label, $token[0]);
    if ($pos === FALSE) {
      $pos = stripos($label, $lemma);
      if ($pos === FALSE) continue; // $pos can be false if search term contains diacritica
    }
    $prefix = substr($label, 0, $pos);
    $pos += strlen($token[0]);
    $suffix = substr($label, $pos);
    if ($suffix === FALSE) $suffix = '';

    $voc = wisski_vocab_ctrl_get($row->vid);

    // $prefix == '' && $suffix == '' can never hold

    $prefix_len = mb_strlen($prefix);
    $suffix_len = mb_strlen($suffix);

    $text_prefix = mb_substr($text_struct['text'], max(0, $token[1] - $prefix_len), min($token[1], $prefix_len));
    $text_suffix = mb_substr($text_struct['text'], $token[2], $prefix_len);

    $prefix_lev = levenshtein($prefix, $text_prefix);
    $suffix_lev = levenshtein($suffix, $text_suffix);
  
    $match = 1 - (1.0 * $prefix_lev + $suffix_lev) / ($prefix_len + $suffix_len);
    $match *= $match; // 


    
/*
    $does_match = TRUE;
    $token_count = 1;
    $range = array($token[1], $token[2]);

    // test if all leading tokens in label match 
    if ($prefix != '') {
      if (!preg_match('/\\s$/', $prefix)) {
        $does_match = FALSE;
      } else {
        $ts = wisski_textanly_analyse_tokenizer(array(), array('text_struct' => $prefix, 'lang' => $text_struct['lang']), $ticket);
        $prefix_tokens = isset($ts['tokens']) ? $ts['tokens'] : array();;
        $token_count += count($prefix_tokens);
        array_reverse($prefix_tokens);
        dpm($prefix_tokens);
        foreach ($prefix_tokens as $p => $t) {
          $tp = $token_pos - $p - 1;
          if ($tp < 0 || strcasecmp($tokens[$tp][0], $t[0]) != 0) {
            $does_match = FALSE;
            $range = array($token[1], $token[2]);
            break;
          } else {
            $range[0] = $tokens[$tp][1];
          }
        }
        
      }
    }

    // test if all trailing tokens in label match
    if ($does_match && $suffix != '') {
      if (!preg_match('/^\\s/', $suffix)) {
        $does_match = FALSE;
      } else {
        $ts = wisski_textanly_analyse_tokenizer(array(), array('text_struct' => $suffix, 'lang' => $text_struct['lang']), $ticket);
        $suffix_tokens = isset($ts['tokens']) ? $ts['tokens'] : array();
        $token_count += count($suffix_tokens);
        dpm($suffix_tokens);
        foreach ($suffix_tokens as $p => $t) {
          $tp = $token_pos + $p + 1;
          if ($tp >= count($tokens) || strcasecmp($tokens[$tp][0], $t[0]) != 0) {
            $does_match = FALSE;
            $range = array($token[1], $token[2]);
            break;
          } else {
            $range[1] = $tokens[$tp][2];
          }
        }
      }
    }
    */

    // we are going to remember the annotation either way
    $anno = array(
      'id' => _wisski_textanly_generate_id(),
      'voc' => $voc->vid,
      'uri' => $row->uri,
      'class' => (string) $voc->group_id,
      'label' => $label,
      'lang' => $row->lang,
      'range' => $range,
      'rank' => $voc->priority / 10.0 + $constraint_offset,
    );
    
    $anno['rank'] += $rank_factor_length_contains * ($prefix_len + $suffix_len) + $rank_offset_contains;
    $anno['rank'] *= $match;
    /*

    // if all tokens matched, we have an overlapping anno, otherwise just a strong guess
    if ($does_match) {

      // do this only for real matches, otherwise it would be too much to compute
      // calculate coordinate offset
      // get the distance to each reference coordinate pair
      // the distance to the nearest pair is taken for offset calculation
      // coordinates must be given in degrees to function
      $contraint_offset = _wisski_textanly_analyse_coord_offset($row, $voc, $settings);

      $anno['rank'] = $anno['rank'] + $rank_factor_length_contains * $token_count + $rank_offset_contains + $constraint_offset;
      $overlap[$voc->priority][] = $anno;
    } else {
      $anno['rank'] = $anno['rank'] + $rank_factor_length_guess * $token_count + $rank_offset_guess;
      $guess[$voc->priority][] = $anno;
    }*/

  }

  $proto_annos = array_merge_recursive($exact, $overlap);
  if (!$proto_annos) $proto_annos = $guess;

  krsort($proto_annos);
  $tmp =  array_reduce($proto_annos, function($a, $b) { return array_merge($a, $b); }, array());

  return $tmp;
}


/** Helper function for wisski_textanly_analyse_vocab
* Calculate rank factor for a place annotation according to its coordinates.
*
* @author Martin Scholz, Florian Wittmann
*
*/
function _wisski_textanly_analyse_coord_offset($row, $voc, $settings) {

  // calculate coordinate offset
  // get the distance to each reference coordinate pair
  // the distance to the nearest pair is taken for offset calculation
  // coordinates must be given in degrees to function
  $rank_offset_coords_lat = isset($settings['rank_offset_coords_lat']) ? $settings['rank_offset_coords_lat'] : -1.5;
  $rank_offset_coords_long = isset($settings['rank_offset_coords_long']) ? $settings['rank_offset_coords_long'] : -1.5;
  $coords = $settings['place_coords'];
  $place_classes = $settings['place_classes'];

  $coord_offset = 0;
  if ($coords && in_array($voc->group_id, $place_classes)) {
    $coord_offset = $rank_offset_coords_lat + $rank_offset_coords_long;
    $c = wisski_vocab_ctrl_lookup(array('latitude', 'longitude'), $voc, $row->uri, NULL);
    if ($c) {
      $c = array('lat' => $c[$row->uri]['latitude'][0]['value'],'long' => $c[$row->uri]['longitude'][0]['value']);
      foreach ($coords as $coord) {
        $o = $rank_offset_coords_lat + $rank_offset_coords_long
          - (180 - abs(abs($coord['lat'] - $c['lat']) % 180 - floor(abs(abs($coord['lat'] - $c['lat']) / 180)))) * $rank_offset_coords_lat / 180
          - (180 - abs(abs($coord['long'] - $c['long']) % 180 - floor(abs(abs($coord['long'] - $c['long']) / 180)))) * $rank_offset_coords_long / 180;
        if ($coord_offset < $o) $coord_offset = $o;
      }
    }
  }

  return $coord_offset;
}


/** Helper function for wisski_textanly_analyse_vocab
* Calculate rank factor for an annotation according to lemma info.
* If a token has a lemma and a certain pos, this affects the propability that
* it may be a certain type of annotation
*
* @author Martin Scholz, Florian Wittmann
*
*/
function _wisski_textanly_analyse_lemma_offset($token, $lemma, $voc, $settings) {

  $rank_offset_has_lemma = isset($settings['rank_offset_has_lemma']) ? $settings['rank_offset_has_lemma'] : array();
  $lemma_offset = 0;
  // check for lemma
  if ($token != '') {
    if (isset($rank_offset_has_lemma[$voc->group_id])) {
      $lemma_offset += $rank_offset_has_lemma[$voc->group_id];
    }
  }
  return $lemma_offset;
}

/** Helper function for wisski_textanly_analyse_vocab
* Calculate rank factor for an annotation according to pos info.
* If a token has a lemma and a certain pos, this affects the propability that
* it may be a certain type of annotation
*
* @author Martin Scholz, Florian Wittmann
*
*/
function _wisski_textanly_analyse_pos_offset($token, $pos, $voc, $settings) {

  $rank_offset_has_pos = isset($settings['rank_offset_has_pos']) ? $settings['rank_offset_has_pos'] : array();
  $pos_offset = 0;
  // check for pos
  if ($pos != '') {
    if (isset($rank_offset_has_pos[$voc->group_id])) {
      if (isset($rank_offset_has_pos[$voc->group_id][$pos])) {
        $pos_offset += $rank_offset_has_pos[$voc->group_id][$pos];
      } else {
        $pos_offset += $rank_offset_has_pos[$voc->group_id][''];
      }
    }
  }
  return $pos_offset;

}



